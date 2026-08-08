<?php

namespace App\Services;

use App\Enums\AmostraStatus;
use App\Enums\BoletimStatus;
use App\Models\Amostra;
use App\Models\AmostraRejeicao;
use App\Models\BoletimAnalise;
use App\Models\BoletimInterno;
use App\Models\CriterioRejeicao;
use App\Models\Requisicao;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Serviço único de workflow parametrizado por disciplina (`laboratorios`),
 * em vez de 8 máquinas de estado duplicadas — ver
 * SIG-IIV-MEMORIA-PROJETO.md secção 15 (deep-dive Onda 3).
 *
 * Fluxo: amostra Recebida -> Triagem (Aceite/Rejeitada) -> [Aceite cria
 * BoletimInterno em_analise] -> resultado registado -> validação
 * dupla/tripla -> aprovado -> comunicado.
 */
class LabWorkflowService
{
    /** Gera um número sequencial `PAT-###-Ano` com base numa tabela+coluna. */
    public function gerarNumeroPat(string $table, string $column): string
    {
        $ano = now()->year;
        $count = DB::table($table)
            ->where($column, 'like', "PAT-%-{$ano}")
            ->count();

        return sprintf('PAT-%03d-%d', $count + 1, $ano);
    }

    /** Aceita a amostra na triagem e abre o Boletim Interno correspondente. */
    public function aceitarAmostra(Amostra $amostra): BoletimInterno
    {
        if ($amostra->status !== AmostraStatus::Recebida) {
            throw ValidationException::withMessages([
                'status' => ['Só é possível aceitar uma amostra ainda em estado "recebida".'],
            ]);
        }

        $amostra->update(['status' => AmostraStatus::Aceite]);

        return BoletimInterno::query()->create([
            'id' => (string) Str::uuid(),
            'amostra_id' => $amostra->id,
            'numero_analise' => $this->gerarNumeroPat('boletins_internos', 'numero_analise'),
            'entrada_em' => now(),
            'inicio_em' => now(),
            'status' => BoletimStatus::EmAnalise,
        ]);
    }

    /** Rejeita a amostra na triagem — exige critério + responsável + assinatura (RN Termo de Rejeição). */
    public function rejeitarAmostra(
        Amostra $amostra,
        CriterioRejeicao $criterio,
        User $responsavel,
        string $assinaturaResponsavel,
        ?string $detalhe = null,
    ): AmostraRejeicao {
        if ($amostra->status !== AmostraStatus::Recebida) {
            throw ValidationException::withMessages([
                'status' => ['Só é possível rejeitar uma amostra ainda em estado "recebida".'],
            ]);
        }

        $amostra->update(['status' => AmostraStatus::Rejeitada]);

        return AmostraRejeicao::query()->create([
            'id' => (string) Str::uuid(),
            'amostra_id' => $amostra->id,
            'criterio_rejeicao_id' => $criterio->id,
            'responsavel_id' => $responsavel->id,
            'detalhe' => $detalhe,
            'assinatura_responsavel' => $assinaturaResponsavel,
            'rejeitada_em' => now(),
        ]);
    }

    /** Regista (ou substitui) o resultado bruto do boletim — abre a fase de validação. */
    public function registarResultado(BoletimInterno $boletim, array $resultado): BoletimAnalise
    {
        if ($boletim->status === BoletimStatus::Aprovado || $boletim->status === BoletimStatus::Comunicado) {
            throw ValidationException::withMessages([
                'status' => ['Não é possível alterar o resultado de um boletim já aprovado/comunicado.'],
            ]);
        }

        $boletimAnalise = $boletim->boletimAnalise ?? new BoletimAnalise(['id' => (string) Str::uuid(), 'boletim_interno_id' => $boletim->id]);
        $boletimAnalise->resultado = $resultado;
        $boletimAnalise->save();

        $boletim->update(['status' => BoletimStatus::ResultadoRegistado]);

        return $boletimAnalise->refresh();
    }

    /**
     * Regista uma assinatura de validação. Quando o número de validadores
     * exigido pela disciplina é atingido, aprova automaticamente o boletim
     * (aplicando primeiro o gate de disciplina, ex. Bromatologia
     * Salmonela=0) — "validação dupla/tripla" e "aprovado" são o mesmo
     * passo lógico neste modelo.
     */
    public function validar(BoletimAnalise $boletimAnalise, User $validador): BoletimAnalise
    {
        $boletim = $boletimAnalise->boletimInterno;
        $laboratorio = $boletim->amostra->requisicao->laboratorio;

        if (! in_array($boletim->status, [BoletimStatus::ResultadoRegistado, BoletimStatus::EmValidacao], true)) {
            throw ValidationException::withMessages([
                'status' => ['O boletim tem de ter um resultado registado antes de poder ser validado.'],
            ]);
        }

        $jaValidaram = $boletimAnalise->validadorIds();

        if (in_array($validador->id, $jaValidaram, true)) {
            throw ValidationException::withMessages([
                'validador' => ['Este utilizador já validou este boletim — a RN exige validadores distintos.'],
            ]);
        }

        $nomeados = $laboratorio->validadores_nomeados;
        if (! empty($nomeados) && ! in_array($validador->id, $nomeados, true)) {
            throw ValidationException::withMessages([
                'validador' => ["A validação de {$laboratorio->code} está restrita à lista de validadores nomeados."],
            ]);
        }

        $slot = match (count($jaValidaram)) {
            0 => 'validador_1_id',
            1 => 'validador_2_id',
            default => 'validador_3_id',
        };
        $boletimAnalise->update([$slot => $validador->id]);

        $totalValidadores = count($boletimAnalise->validadorIds());

        if ($totalValidadores < $laboratorio->validador_count) {
            $boletim->update(['status' => BoletimStatus::EmValidacao]);

            return $boletimAnalise->refresh();
        }

        $this->aplicarGateDisciplina($laboratorio->code, $boletimAnalise->resultado ?? []);

        $boletimAnalise->update([
            'validado_em' => now(),
            'aprovado_por_id' => $validador->id,
            'aprovado_em' => now(),
        ]);
        $boletim->update(['status' => BoletimStatus::Aprovado, 'conclusao_em' => now()]);

        return $boletimAnalise->refresh();
    }

    /** Comunica (emite ao cliente) um boletim já aprovado. */
    public function comunicar(BoletimInterno $boletim): BoletimInterno
    {
        if ($boletim->status !== BoletimStatus::Aprovado) {
            throw ValidationException::withMessages([
                'status' => ['Só é possível comunicar um boletim já aprovado.'],
            ]);
        }

        $boletim->update(['status' => BoletimStatus::Comunicado]);
        $boletim->boletimAnalise?->update(['comunicado_em' => now()]);

        return $boletim->refresh();
    }

    /**
     * Regras nomeadas por disciplina que bloqueiam a aprovação — não são
     * apenas avisos de UI (RN Bromatologia). Novas disciplinas com gates
     * próprios acrescentam-se aqui.
     */
    private function aplicarGateDisciplina(string $codigoDisciplina, array $resultado): void
    {
        if ($codigoDisciplina === 'BR') {
            $salmonela = $resultado['parametros']['salmonela'] ?? null;
            if ($salmonela !== null && (float) $salmonela !== 0.0) {
                throw ValidationException::withMessages([
                    'resultado' => ['Bromatologia: resultado de Salmonela tem de ser 0 para aprovar o boletim.'],
                ]);
            }
        }
    }

    /** Gera o número da requisição e, para cada amostra do lote, o seu número de item. */
    public function gerarNumeroRequisicao(): string
    {
        return $this->gerarNumeroPat('requisicoes', 'numero');
    }

    public function gerarNumeroAmostra(Requisicao $requisicao, int $indice): string
    {
        return "{$requisicao->numero}-A{$indice}";
    }
}

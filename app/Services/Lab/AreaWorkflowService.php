<?php

namespace App\Services\Lab;

use App\Enums\AmostraStatus;
use App\Enums\BoletimInternoStatus;
use App\Models\Amostra;
use App\Models\AmostraRejeicao;
use App\Models\BoletimInterno;
use App\Models\BoletimInternoAmostra;
use App\Models\CriterioRejeicao;
use App\Models\Ensaio;
use App\Models\ExameCatalogo;
use App\Models\RequisicaoExame;
use App\Models\User;
use App\Models\Validacao;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Fase 2b — trabalho dentro da área (DEF-002): iniciar ensaio, boletim de
 * ensaio por amostra × exame (método/POP, equipamento, lote de reagente,
 * executor), submissão para validação e validação multi-interveniente.
 *
 * Regras de validação (nenhum técnico valida sozinho):
 *  - o validador nunca é executor de um ensaio do BI;
 *  - validadores distintos entre si (unique BI × validador);
 *  - Bacteriologia (AB) e qualquer área com `validadores_nomeados`: só a
 *    lista nomeada (D.0#5 — lista real a confirmar com o laboratório);
 *  - Bromatologia (BR): só avança com Salmonela = 0;
 *  - ao atingir `validador_count` o BI passa a `validado` — NUNCA a
 *    emitido: a emissão é exclusiva da CGISC (EmissaoService).
 */
class AreaWorkflowService
{
    public const RESULTADOS_QUALITATIVOS = ['positivo', 'negativo', 'inconclusivo'];

    public function __construct(private readonly LabSupport $support) {}

    public function iniciarEnsaio(BoletimInterno $bi, User $user): BoletimInterno
    {
        $this->support->exigirMembroDaArea($user, $bi);

        return DB::transaction(function () use ($bi, $user) {
            $this->support->transitar($bi, BoletimInternoStatus::EmAnalise, [
                'inicio_em' => $bi->inicio_em ?? now(),
                'responsavel_tecnico_id' => $bi->responsavel_tecnico_id ?? $user->id,
            ]);
            DB::table('boletim_interno_amostras')
                ->where('boletim_interno_id', $bi->id)
                ->where('estado', BoletimInternoAmostra::PENDENTE)
                ->update(['estado' => BoletimInternoAmostra::EM_ENSAIO, 'updated_at' => now()]);

            foreach ($bi->amostras()->get() as $amostra) {
                if (in_array($amostra->status, [AmostraStatus::Aceite, AmostraStatus::AceiteComReserva], true)) {
                    $this->support->transitarAmostra($amostra, AmostraStatus::EmProcessamento);
                }
                $local = $this->support->localArea($bi->laboratorio);
                $this->support->movimento($amostra, 'ensaio_iniciado', $user, $bi, $local, $local);
            }

            return $bi->refresh();
        });
    }

    /**
     * Regista (ou corrige) o boletim de ensaio de uma amostra × exame. O
     * utilizador que regista é o executor.
     *
     * @param  array<string, mixed>  $data
     */
    public function registarEnsaio(BoletimInterno $bi, User $user, array $data): Ensaio
    {
        $this->support->exigirMembroDaArea($user, $bi);
        $this->support->exigirEstado($bi, [BoletimInternoStatus::EmAnalise], 'registar ensaios');

        $amostra = $bi->amostras()->where('amostras.id', $data['amostraId'])->first();
        if (! $amostra || $amostra->pivot->estado === BoletimInternoAmostra::REJEITADA_NA_AREA) {
            throw ValidationException::withMessages(['amostraId' => ['A amostra não pertence (activa) a este Boletim Interno.']]);
        }

        $exame = ExameCatalogo::query()->find($data['exameId']);
        if (! $exame || $exame->laboratorio_id !== $bi->laboratorio_id) {
            throw ValidationException::withMessages(['exameId' => ['O exame tem de pertencer ao catálogo desta área.']]);
        }
        $pedidos = $bi->examesPedidos()->get();
        if ($pedidos->isNotEmpty() && ! $pedidos->contains(fn (RequisicaoExame $p) => $p->exame_id === $exame->id && $p->aplicaA($amostra->id))) {
            throw ValidationException::withMessages(['exameId' => ["O exame {$exame->codigo} não foi pedido para a amostra {$amostra->numero}."]]);
        }

        $resultado = $data['resultado'] ?? null;
        if ($resultado !== null) {
            $this->validarResultado($exame, $resultado);
        }
        $qualitativo = $data['resultadoQualitativo'] ?? null;
        if ($qualitativo !== null && ! in_array($qualitativo, self::RESULTADOS_QUALITATIVOS, true)) {
            throw ValidationException::withMessages(['resultadoQualitativo' => ['Valor inválido (positivo|negativo|inconclusivo).']]);
        }

        return DB::transaction(function () use ($bi, $user, $data, $amostra, $exame, $resultado, $qualitativo) {
            $ensaio = Ensaio::query()->firstOrNew([
                'boletim_interno_id' => $bi->id,
                'amostra_id' => $amostra->id,
                'exame_id' => $exame->id,
            ]);
            if (! $ensaio->exists) {
                $ensaio->id = (string) Str::uuid();
                $ensaio->iniciado_em = now();
            }
            $ensaio->fill([
                'tecnica' => $data['tecnica'] ?? $exame->tecnica_metodo,
                'pop_codigo' => $data['popCodigo'] ?? $exame->pop_codigo,
                'equipamento_id' => $data['equipamentoId'] ?? null,
                'lote_reagente' => $data['loteReagente'] ?? null,
                'executor_id' => $user->id,
                'segundo_interveniente_id' => $data['segundoIntervenienteId'] ?? null,
                'visualizador_id' => $data['visualizadorId'] ?? null,
                'resultado' => $resultado,
                'resultado_qualitativo' => $qualitativo,
                'interpretacao' => $data['interpretacao'] ?? null,
                'concluido_em' => $resultado !== null || $qualitativo !== null ? now() : null,
            ]);
            $ensaio->save();

            $local = $this->support->localArea($bi->laboratorio);
            $this->support->movimento($amostra, 'resultado', $user, $bi, $local, $local,
                "Ensaio {$exame->codigo}".($data['equipamentoId'] ?? null ? " · equip. {$data['equipamentoId']}" : ''));

            return $ensaio->refresh();
        });
    }

    /**
     * Valida `resultado` contra `exames_catalogo.campos_resultado`
     * ([{chave, tipo: numero|texto|booleano|opcao, obrigatorio?, opcoes?}]).
     *
     * @param  array<string, mixed>  $resultado
     */
    public function validarResultado(ExameCatalogo $exame, array $resultado): void
    {
        $campos = $exame->campos_resultado ?? [];
        if ($campos === []) {
            return;
        }

        $erros = [];
        $chaves = [];
        foreach ($campos as $campo) {
            $chave = $campo['chave'];
            $chaves[] = $chave;
            $obrigatorio = $campo['obrigatorio'] ?? true;
            if (! array_key_exists($chave, $resultado) || $resultado[$chave] === null || $resultado[$chave] === '') {
                if ($obrigatorio) {
                    $erros["resultado.{$chave}"] = ["Parâmetro obrigatório \"{$chave}\" em falta."];
                }

                continue;
            }
            $valor = $resultado[$chave];
            $ok = match ($campo['tipo'] ?? 'texto') {
                'numero' => is_numeric($valor),
                'booleano' => is_bool($valor),
                'opcao' => in_array($valor, $campo['opcoes'] ?? [], true),
                default => is_string($valor) || is_numeric($valor),
            };
            if (! $ok) {
                $erros["resultado.{$chave}"] = ["Valor inválido para \"{$chave}\" (tipo {$campo['tipo']})."];
            }
        }
        foreach (array_diff(array_keys($resultado), $chaves) as $desconhecida) {
            $erros["resultado.{$desconhecida}"] = ["Parâmetro \"{$desconhecida}\" não existe no exame {$exame->codigo}."];
        }

        if ($erros) {
            throw ValidationException::withMessages($erros);
        }
    }

    /**
     * Rejeição na área (Termo de Rejeição, fase "area", 2 assinaturas). Se
     * TODAS as amostras do BI ficarem rejeitadas o BI passa a
     * `rejeitado_na_area` (terminal).
     *
     * @param  list<string>  $amostraIds
     */
    public function rejeitarAmostras(
        BoletimInterno $bi,
        User $responsavel,
        string $assinatura,
        User $segundo,
        string $assinaturaSegundo,
        CriterioRejeicao $criterio,
        array $amostraIds,
        ?string $detalhe = null,
    ): BoletimInterno {
        $this->support->exigirMembroDaArea($responsavel, $bi);
        $this->support->exigirEstado($bi, [BoletimInternoStatus::RecebidoArea, BoletimInternoStatus::EmAnalise], 'rejeitar amostras na área');
        if ($segundo->id === $responsavel->id) {
            throw ValidationException::withMessages(['segundoResponsavelId' => ['O Termo de Rejeição exige a assinatura de dois responsáveis diferentes.']]);
        }

        $amostras = $bi->amostras()->whereIn('amostras.id', $amostraIds)->get();
        if ($amostras->count() !== count($amostraIds)) {
            throw ValidationException::withMessages(['amostraIds' => ['Todas as amostras indicadas têm de pertencer a este Boletim Interno.']]);
        }

        return DB::transaction(function () use ($bi, $responsavel, $assinatura, $segundo, $assinaturaSegundo, $criterio, $amostras, $detalhe) {
            $local = $this->support->localArea($bi->laboratorio);
            foreach ($amostras as $amostra) {
                AmostraRejeicao::query()->create([
                    'id' => (string) Str::uuid(),
                    'amostra_id' => $amostra->id,
                    'criterio_rejeicao_id' => $criterio->id,
                    'responsavel_id' => $responsavel->id,
                    'detalhe' => $detalhe,
                    'assinatura_responsavel' => $assinatura,
                    'rejeitada_em' => now(),
                    'fase' => 'area',
                    'laboratorio_id' => $bi->laboratorio_id,
                    'boletim_interno_id' => $bi->id,
                    'segundo_responsavel_id' => $segundo->id,
                    'assinatura_segundo' => $assinaturaSegundo,
                    'origem_notificada_em' => now(), // stub de notificação à origem
                ]);
                $bi->amostras()->updateExistingPivot($amostra->id, ['estado' => BoletimInternoAmostra::REJEITADA_NA_AREA]);
                $this->support->movimento($amostra, 'rejeitada', $responsavel, $bi, $local, null, "Rejeitada na área ({$criterio->codigo}): {$criterio->motivo}");
            }

            $activas = $bi->amostras()->wherePivot('estado', '!=', BoletimInternoAmostra::REJEITADA_NA_AREA)->count();
            if ($activas === 0) {
                $this->support->transitar($bi, BoletimInternoStatus::RejeitadoNaArea, ['conclusao_em' => now()]);
                $this->support->concluirAmostrasSeTerminadas($bi);
                $this->support->sincronizarRequisicao($bi->requisicao);
            }

            return $bi->refresh();
        });
    }

    /** Fecha o registo de resultados: exige ensaio com resultado para cada amostra activa × exame pedido. */
    public function submeterValidacao(BoletimInterno $bi, User $user, ?string $resultadoGlobal = null): BoletimInterno
    {
        $this->support->exigirMembroDaArea($user, $bi);
        $this->support->exigirEstado($bi, [BoletimInternoStatus::EmAnalise], 'submeter para validação');

        if ($resultadoGlobal !== null && ! in_array($resultadoGlobal, self::RESULTADOS_QUALITATIVOS, true)) {
            throw ValidationException::withMessages(['resultadoGlobal' => ['Valor inválido (positivo|negativo|inconclusivo).']]);
        }

        $activas = $bi->amostras()->wherePivot('estado', '!=', BoletimInternoAmostra::REJEITADA_NA_AREA)->get();
        $pedidos = $bi->examesPedidos()->with('exame')->get();
        $ensaios = $bi->ensaios()->get();

        $emFalta = [];
        foreach ($activas as $amostra) {
            foreach ($pedidos->filter(fn (RequisicaoExame $p) => $p->aplicaA($amostra->id)) as $pedido) {
                $ensaio = $ensaios->first(fn (Ensaio $e) => $e->amostra_id === $amostra->id && $e->exame_id === $pedido->exame_id);
                if (! $ensaio || ($ensaio->resultado === null && $ensaio->resultado_qualitativo === null)) {
                    $emFalta[] = "{$amostra->numero} × {$pedido->exame?->codigo}";
                }
            }
        }
        if ($pedidos->isEmpty() && $ensaios->isEmpty()) {
            $emFalta[] = 'nenhum ensaio registado';
        }
        if ($emFalta !== []) {
            throw ValidationException::withMessages(['ensaios' => ['Resultados em falta: '.implode(', ', array_unique($emFalta)).'.']]);
        }

        if ($resultadoGlobal === null) {
            $qualitativos = $ensaios->pluck('resultado_qualitativo')->filter();
            $resultadoGlobal = match (true) {
                $qualitativos->contains('positivo') => 'positivo',
                $qualitativos->isNotEmpty() && $qualitativos->every(fn ($q) => $q === 'negativo') => 'negativo',
                $qualitativos->isNotEmpty() => 'inconclusivo',
                default => null,
            };
        }

        return DB::transaction(function () use ($bi, $user, $resultadoGlobal) {
            $this->support->transitar($bi, BoletimInternoStatus::ResultadoRegistado, ['resultado_global' => $resultadoGlobal]);
            DB::table('boletim_interno_amostras')
                ->where('boletim_interno_id', $bi->id)
                ->whereIn('estado', [BoletimInternoAmostra::PENDENTE, BoletimInternoAmostra::EM_ENSAIO])
                ->update(['estado' => BoletimInternoAmostra::CONCLUIDA, 'updated_at' => now()]);
            $local = $this->support->localArea($bi->laboratorio);
            $this->support->movimentoBi($bi, 'resultado', $user, $local, $local, 'Resultados submetidos para validação.');

            return $bi->refresh();
        });
    }

    public function validar(BoletimInterno $bi, User $validador, ?string $assinatura = null, ?string $observacao = null, ?string $tipo = null): BoletimInterno
    {
        $this->support->exigirEstado($bi, [BoletimInternoStatus::ResultadoRegistado, BoletimInternoStatus::EmValidacao], 'validar');
        $laboratorio = $bi->laboratorio;

        $executores = $bi->ensaios()->pluck('executor_id')->unique()->all();
        if (in_array($validador->id, $executores, true)) {
            throw ValidationException::withMessages([
                'validador' => ['O validador não pode ser o executor de um ensaio deste Boletim Interno (nenhum técnico valida o seu próprio trabalho).'],
            ]);
        }

        $jaValidaram = $bi->validacoes()->pluck('validador_id')->all();
        if (in_array($validador->id, $jaValidaram, true)) {
            throw ValidationException::withMessages(['validador' => ['Este utilizador já validou este Boletim Interno — a RN exige validadores distintos.']]);
        }

        $nomeados = $laboratorio->validadores_nomeados;
        if (! empty($nomeados) && ! in_array($validador->id, $nomeados, true)) {
            throw ValidationException::withMessages([
                'validador' => ["A validação de {$laboratorio->code} está restrita à lista de validadores nomeados."],
            ]);
        }

        $ordem = count($jaValidaram) + 1;
        // O executor nunca valida, por isso mesmo com validador_count=1 há
        // sempre 2 intervenientes distintos (executor + validador).
        $necessarios = max(1, (int) $laboratorio->validador_count);

        return DB::transaction(function () use ($bi, $validador, $assinatura, $observacao, $tipo, $laboratorio, $ordem, $necessarios) {
            if ($ordem >= $necessarios) {
                // Gate de disciplina antes de fechar a validação (falha = 422 e nada é gravado).
                $this->aplicarGateDisciplina($bi);
            }

            Validacao::query()->create([
                'id' => (string) Str::uuid(),
                'boletim_interno_id' => $bi->id,
                'validador_id' => $validador->id,
                'ordem' => $ordem,
                'tipo' => $tipo ?? ($laboratorio->responsavel_qualidade_id === $validador->id
                    ? 'qualidade'
                    : match ($ordem) {
                        1 => 'segundo',
                        2 => 'terceiro',
                        default => 'hierarquica',
                    }),
                'assinatura' => $assinatura,
                'observacao' => $observacao,
            ]);

            $local = $this->support->localArea($laboratorio);
            if ($ordem < $necessarios) {
                $this->support->transitar($bi, BoletimInternoStatus::EmValidacao);
                $this->support->movimentoBi($bi, 'validada', $validador, $local, $local, "Validação {$ordem}/{$necessarios}.");
            } else {
                $this->support->transitar($bi, BoletimInternoStatus::Validado, ['conclusao_em' => now()]);
                $this->support->movimentoBi($bi, 'validada', $validador, $local, $local, "Validação {$ordem}/{$necessarios} — BI validado.");
            }

            return $bi->refresh();
        });
    }

    /** Devolve o BI validado à CGISC (o BI é o documento que circula). */
    public function devolverACgisc(BoletimInterno $bi, User $user): BoletimInterno
    {
        $this->support->exigirMembroDaArea($user, $bi);

        return DB::transaction(function () use ($bi, $user) {
            $this->support->transitar($bi, BoletimInternoStatus::Devolvido, [
                'devolvido_por_id' => $user->id,
                'devolvido_em' => now(),
            ]);
            $this->support->movimentoBi($bi, 'devolvida', $user, $this->support->localArea($bi->laboratorio), LabSupport::CGISC);

            return $bi->refresh();
        });
    }

    /**
     * Regras nomeadas por disciplina que bloqueiam a validação final — não
     * são apenas avisos de UI. Bromatologia (BR): Salmonela tem de ser 0.
     */
    private function aplicarGateDisciplina(BoletimInterno $bi): void
    {
        if ($bi->laboratorio?->code !== 'BR') {
            return;
        }

        foreach ($bi->ensaios()->get() as $ensaio) {
            $resultado = $ensaio->resultado ?? [];
            $salmonela = $resultado['salmonela'] ?? ($resultado['parametros']['salmonela'] ?? null);
            if ($salmonela !== null && (float) $salmonela !== 0.0) {
                throw ValidationException::withMessages([
                    'resultado' => ['Bromatologia: resultado de Salmonela tem de ser 0 para validar o boletim.'],
                ]);
            }
        }
    }
}

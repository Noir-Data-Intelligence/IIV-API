<?php

namespace App\Services\Lab;

use App\Enums\AmostraStatus;
use App\Enums\BoletimInternoStatus;
use App\Models\Amostra;
use App\Models\BoletimInterno;
use App\Models\BoletimInternoAmostra;
use App\Models\ExameCatalogo;
use App\Models\Laboratorio;
use App\Models\Requisicao;
use App\Models\RequisicaoExame;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Fase 2a — circulação física do BI entre a CGISC e as áreas (DEF-002):
 * encaminhar (CGISC -> trânsito), receber na área (só a equipa da área,
 * via `laboratorio_tecnicos`) e reencaminhar para outra área. Cada passo
 * fica registado em `amostra_movimentos`.
 */
class EncaminhamentoService
{
    public function __construct(
        private readonly LabSupport $support,
        private readonly RecepcaoService $recepcao,
    ) {}

    public function encaminhar(BoletimInterno $bi, User $user): BoletimInterno
    {
        return DB::transaction(function () use ($bi, $user) {
            $this->support->transitar($bi, BoletimInternoStatus::EmTransito, [
                'encaminhado_por_id' => $user->id,
                'encaminhado_em' => now(),
            ]);
            $this->support->movimentoBi($bi, 'encaminhada', $user, LabSupport::CGISC, $this->support->localArea($bi->laboratorio));

            return $bi->refresh();
        });
    }

    /**
     * Encaminha de uma vez todos os BI ainda `gerado` de um processo.
     *
     * @return Collection<int, BoletimInterno>
     */
    public function encaminharTodos(Requisicao $requisicao, User $user): Collection
    {
        $gerados = $requisicao->boletinsInternos()->where('status', BoletimInternoStatus::Gerado->value)->get();
        if ($gerados->isEmpty()) {
            throw ValidationException::withMessages(['status' => ['Não há Boletins Internos por encaminhar neste processo.']]);
        }

        return $gerados->map(fn (BoletimInterno $bi) => $this->encaminhar($bi, $user));
    }

    /** Recepção explícita na área — exige pertencer à equipa da área (403 caso contrário). */
    public function receberNaArea(BoletimInterno $bi, User $user): BoletimInterno
    {
        $this->support->exigirMembroDaArea($user, $bi);

        return DB::transaction(function () use ($bi, $user) {
            $this->support->transitar($bi, BoletimInternoStatus::RecebidoArea, [
                'recebido_area_por_id' => $user->id,
                'recebido_area_em' => now(),
            ]);
            $local = $this->support->localArea($bi->laboratorio);
            foreach ($bi->amostras()->get() as $amostra) {
                $amostra->update(['localizacao_actual' => $local]);
                $this->support->movimento($amostra, 'recebida_area', $user, $bi, LabSupport::CGISC, $local);
            }
            $this->support->sincronizarRequisicao($bi->requisicao);

            return $bi->refresh();
        });
    }

    /**
     * Reencaminha amostras de um BI para outra área: cria um NOVO BI na
     * área de destino (o BI de origem mantém o seu estado e segue o seu
     * próprio fluxo). Com `duplicar`, cria uma sub-amostra derivada
     * (`amostra_origem_id`) em vez de partilhar a mesma amostra.
     *
     * @param  list<string>  $exameIds  exames do catálogo da área de destino
     * @param  list<string>|null  $amostraIds  null = todas as amostras activas do BI
     */
    public function reencaminhar(
        BoletimInterno $bi,
        User $user,
        Laboratorio $destino,
        array $exameIds,
        ?array $amostraIds,
        string $motivo,
        bool $duplicar = false,
    ): BoletimInterno {
        $this->support->exigirMembroDaArea($user, $bi);

        if ($bi->status->isTerminal()) {
            throw ValidationException::withMessages(['status' => ['Não é possível reencaminhar a partir de um Boletim Interno já terminado.']]);
        }
        if ($destino->id === $bi->laboratorio_id) {
            throw ValidationException::withMessages(['laboratorioId' => ['A área de destino tem de ser diferente da área actual.']]);
        }

        $exames = ExameCatalogo::query()->whereIn('id', $exameIds)->get();
        if ($exames->isEmpty() || $exames->contains(fn (ExameCatalogo $e) => $e->laboratorio_id !== $destino->id || ! $e->activo)) {
            throw ValidationException::withMessages(['exameIds' => ['Os exames têm de existir, estar activos e pertencer à área de destino.']]);
        }

        $activas = $bi->amostras()->wherePivot('estado', '!=', BoletimInternoAmostra::REJEITADA_NA_AREA)->get();
        $amostras = $amostraIds === null ? $activas : $activas->whereIn('id', $amostraIds)->values();
        if ($amostras->isEmpty() || ($amostraIds !== null && $amostras->count() !== count($amostraIds))) {
            throw ValidationException::withMessages(['amostraIds' => ['As amostras indicadas têm de pertencer (activas) a este Boletim Interno.']]);
        }

        return DB::transaction(function () use ($bi, $user, $destino, $exames, $amostras, $motivo, $duplicar) {
            $requisicao = $bi->requisicao;
            $origemLocal = $this->support->localArea($bi->laboratorio);
            $destinoLocal = $this->support->localArea($destino);

            if ($duplicar) {
                $amostras = $amostras->map(function (Amostra $original) use ($user, $bi) {
                    $n = Amostra::query()->where('amostra_origem_id', $original->id)->count() + 1;
                    $copia = Amostra::query()->create([
                        'id' => (string) Str::uuid(),
                        'requisicao_id' => $original->requisicao_id,
                        'numero' => "{$original->numero}-D{$n}",
                        'tipo_amostra' => $original->tipo_amostra,
                        'origem_matriz' => $original->origem_matriz,
                        'ponto_colheita' => $original->ponto_colheita,
                        'latitude' => $original->latitude,
                        'longitude' => $original->longitude,
                        'colhida_por' => $original->colhida_por,
                        'sujeito' => $original->sujeito,
                        'recebida_em' => now(),
                        'status' => AmostraStatus::Aceite,
                        'aceite_em' => now(),
                        'codigo_barras' => $original->codigo_barras ? "{$original->codigo_barras}-D{$n}" : null,
                        'amostra_origem_id' => $original->id,
                        'localizacao_actual' => $original->localizacao_actual,
                    ]);
                    $this->support->movimento($copia, 'recebida', $user, $bi, null, $original->localizacao_actual, "Sub-amostra derivada de {$original->numero}.");

                    return $copia;
                });
            }

            $numero = $this->recepcao->numeroBi($requisicao->pat_numero ?? $requisicao->numero, $destino);
            $slaHoras = $exames->pluck('sla_horas')->filter()->max() ?? $destino->sla_horas;

            $novo = BoletimInterno::query()->create([
                'id' => (string) Str::uuid(),
                'requisicao_id' => $requisicao->id,
                'laboratorio_id' => $destino->id,
                'numero' => $numero,
                'numero_analise' => $numero,
                'exames' => $exames->pluck('codigo')->values()->all(),
                'entrada_em' => now(),
                'prazo_em' => $slaHoras ? now()->addHours($slaHoras) : null,
                'status' => BoletimInternoStatus::Gerado,
                'bi_origem_id' => $bi->id,
            ]);
            $novo->amostras()->attach($amostras->mapWithKeys(fn (Amostra $a) => [$a->id => ['id' => (string) Str::uuid(), 'estado' => BoletimInternoAmostra::PENDENTE]])->all());

            foreach ($exames as $exame) {
                foreach ($amostras as $amostra) {
                    RequisicaoExame::query()->create([
                        'id' => (string) Str::uuid(),
                        'requisicao_id' => $requisicao->id,
                        'amostra_id' => $amostra->id,
                        'exame_id' => $exame->id,
                        'laboratorio_id' => $destino->id,
                        'boletim_interno_id' => $novo->id,
                    ]);
                }
            }

            foreach ($amostras as $amostra) {
                $this->support->movimento($amostra, 'reencaminhada', $user, $novo, $origemLocal, $destinoLocal, "Reencaminhada de {$bi->numero}: {$motivo}");
            }

            $this->support->sincronizarRequisicao($requisicao);

            return $novo->refresh();
        });
    }
}

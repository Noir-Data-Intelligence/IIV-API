<?php

namespace App\Services\Lab;

use App\Enums\AppRole;
use App\Enums\BoletimInternoStatus;
use App\Enums\RequisicaoStatus;
use App\Models\BoletimAnalise;
use App\Models\BoletimInterno;
use App\Models\BoletimInternoAmostra;
use App\Models\Ensaio;
use App\Models\Notification;
use App\Models\Requisicao;
use App\Models\User;
use App\Models\Validacao;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Fase 3 — entrega de resultados (DEF-003). Só a CGISC: recebe
 * explicitamente o BI devolvido pela área, pode devolvê-lo para correcção,
 * EMITE o Boletim de Análises oficial (nunca o técnico), regista a entrega
 * à origem e encerra o processo quando todos os BI estão emitidos ou
 * rejeitados.
 */
class EmissaoService
{
    public function __construct(
        private readonly LabSupport $support,
        private readonly NumeracaoService $numeracao,
    ) {}

    public function receberDevolucao(BoletimInterno $bi, User $user): BoletimInterno
    {
        return DB::transaction(function () use ($bi, $user) {
            $this->support->transitar($bi, BoletimInternoStatus::RecebidoCgisc, [
                'recebido_cgisc_por_id' => $user->id,
                'recebido_cgisc_em' => now(),
            ]);
            $this->support->movimentoBi($bi, 'recebida_cgisc', $user, $this->support->localArea($bi->laboratorio), LabSupport::CGISC);
            $this->support->sincronizarRequisicao($bi->requisicao);

            return $bi->refresh();
        });
    }

    /** Aresta de retorno recebido_cgisc -> em_analise: anula as validações (têm de ser refeitas). */
    public function devolverParaCorreccao(BoletimInterno $bi, User $user, string $motivo): BoletimInterno
    {
        return DB::transaction(function () use ($bi, $user, $motivo) {
            $anuladas = $bi->validacoes()->with('validador')->get();
            $this->support->transitar($bi, BoletimInternoStatus::EmAnalise, [
                'motivo_correccao' => $motivo,
                'conclusao_em' => null,
                'devolvido_em' => null,
                'devolvido_por_id' => null,
                'recebido_cgisc_em' => null,
                'recebido_cgisc_por_id' => null,
            ]);
            // As validações anteriores deixam de valer; ficam no trilho de
            // auditoria (Validacao usa LogsAuditTrail) e no movimento abaixo.
            $anuladas->each(fn (Validacao $v) => $v->delete());
            DB::table('boletim_interno_amostras')
                ->where('boletim_interno_id', $bi->id)
                ->where('estado', BoletimInternoAmostra::CONCLUIDA)
                ->update(['estado' => BoletimInternoAmostra::EM_ENSAIO, 'updated_at' => now()]);

            $nomes = $anuladas->map(fn (Validacao $v) => $v->validador?->full_name ?? $v->validador_id)->implode(', ');
            $this->support->movimentoBi($bi, 'devolvida_correccao', $user, LabSupport::CGISC, $this->support->localArea($bi->laboratorio),
                "Correcção pedida: {$motivo}".($nomes !== '' ? " (validações anuladas: {$nomes})" : ''));
            $this->support->sincronizarRequisicao($bi->requisicao);

            return $bi->refresh();
        });
    }

    /**
     * Emite o Boletim de Análises oficial a partir de um BI `recebido_cgisc`:
     * número próprio, snapshot consolidado e imutável em `conteudo`,
     * emissor/assinante e hash da assinatura. PDF real: stub (pdf_path null).
     */
    public function emitirBoletimAnalises(BoletimInterno $bi, User $user): BoletimAnalise
    {
        $this->support->exigirEstado($bi, [BoletimInternoStatus::RecebidoCgisc], 'emitir o Boletim de Análises');

        return DB::transaction(function () use ($bi, $user) {
            $bi->load(['requisicao', 'laboratorio', 'amostras', 'ensaios.exame', 'ensaios.executor', 'validacoes.validador']);
            $requisicao = $bi->requisicao;

            $conteudo = [
                'processo' => [
                    'pat' => $requisicao->pat_numero,
                    'requisicao' => $requisicao->numero,
                    'cliente' => $requisicao->cliente_nome,
                    'origemTipo' => $requisicao->origem_tipo,
                    'veterinario' => $requisicao->veterinario_responsavel,
                ],
                'area' => ['codigo' => $bi->laboratorio?->code, 'nome' => $bi->laboratorio?->name],
                'boletimInterno' => $bi->numero,
                'resultadoGlobal' => $bi->resultado_global,
                'amostras' => $bi->amostras->map(fn ($a) => [
                    'numero' => $a->numero,
                    'codigoBarras' => $a->codigo_barras,
                    'tipo' => $a->tipo_amostra,
                    'estado' => $a->pivot->estado,
                    'aceiteComReserva' => (bool) $a->aceite_com_reserva,
                    'reservaMotivo' => $a->reserva_motivo,
                ])->values()->all(),
                'ensaios' => $bi->ensaios->map(fn (Ensaio $e) => [
                    'amostraId' => $e->amostra_id,
                    'exame' => $e->exame?->codigo,
                    'exameNome' => $e->exame?->nome,
                    'tecnica' => $e->tecnica,
                    'pop' => $e->pop_codigo,
                    'resultado' => $e->resultado,
                    'resultadoQualitativo' => $e->resultado_qualitativo,
                    'interpretacao' => $e->interpretacao,
                    'executor' => $e->executor?->full_name,
                    'concluidoEm' => $e->concluido_em?->toISOString(),
                ])->values()->all(),
                'validacoes' => $bi->validacoes->map(fn (Validacao $v) => [
                    'ordem' => $v->ordem,
                    'tipo' => $v->tipo,
                    'validador' => $v->validador?->full_name,
                    'em' => $v->created_at?->toISOString(),
                ])->values()->all(),
                'emitidoEm' => now()->toISOString(),
                'emitidoPor' => $user->full_name,
            ];

            // boletim_interno_id é UNIQUE em boletins_analises: um BA antigo
            // (modelo anterior) para o mesmo BI é reaproveitado.
            $ba = BoletimAnalise::query()->firstOrNew(['boletim_interno_id' => $bi->id]);
            if (! $ba->exists) {
                $ba->id = (string) Str::uuid();
            }
            $ba->fill([
                'numero' => $ba->numero ?? $this->numeracao->boletimAnalises(),
                'requisicao_id' => $requisicao->id,
                'laboratorio_id' => $bi->laboratorio_id,
                'conteudo' => $conteudo,
                'emitido_por_id' => $user->id,
                'emitido_em' => now(),
                'assinado_por_id' => $user->id,
                'assinatura_hash' => hash('sha256', json_encode($conteudo).'|'.$user->id),
                'pdf_path' => null, // STUB: geração real de PDF a implementar.
            ]);
            $ba->save();

            $this->support->transitar($bi, BoletimInternoStatus::Emitido);
            $this->support->movimentoBi($bi, 'emitida', $user, LabSupport::CGISC, LabSupport::CGISC, "Boletim de Análises {$ba->numero} emitido.");

            // D.0 (a confirmar): origem pública (serviços veterinários
            // oficiais) => notificação automática obrigatória ao ISV.
            if ($requisicao->origem_tipo === 'publica') {
                $this->notificarIsv($ba, $requisicao);
            }

            $this->support->concluirAmostrasSeTerminadas($bi);
            $this->support->sincronizarRequisicao($requisicao);

            return $ba->refresh();
        });
    }

    /**
     * Regista a entrega do BA à origem. Bloqueia se a área exigir pagamento
     * prévio (`laboratorios.exige_pagamento_para_entrega`, D.0#4, default
     * false) e o pagamento não vier confirmado — STUB: `pagamentoConfirmado`
     * vem no payload porque ainda não existe módulo de pagamentos.
     */
    public function entregar(BoletimAnalise $ba, User $user, string $entregueA, string $canal, bool $pagamentoConfirmado = false): BoletimAnalise
    {
        if (! $ba->emitido_em) {
            throw ValidationException::withMessages(['status' => ['Só é possível entregar um Boletim de Análises já emitido.']]);
        }
        if ($ba->entregue_em) {
            throw ValidationException::withMessages(['status' => ['Este Boletim de Análises já foi entregue.']]);
        }
        if ($ba->laboratorio?->exige_pagamento_para_entrega && ! $pagamentoConfirmado) {
            throw ValidationException::withMessages([
                'pagamentoConfirmado' => ['Esta área exige confirmação de pagamento antes da entrega do Boletim de Análises.'],
            ]);
        }

        return DB::transaction(function () use ($ba, $user, $entregueA, $canal) {
            $ba->update([
                'entregue_em' => now(),
                'entregue_a' => $entregueA,
                'canal_entrega' => $canal,
            ]);
            if ($canal === 'isv' && ! $ba->isv_notificado_em) {
                $this->notificarIsv($ba, $ba->requisicao);
            }
            $bi = $ba->boletimInterno;
            if ($bi) {
                $this->support->movimentoBi($bi, 'entregue', $user, LabSupport::CGISC, $entregueA, "Entregue via {$canal}.");
            }

            return $ba->refresh();
        });
    }

    /** Encerra o processo: exige todos os BI emitidos ou rejeitados na área. */
    public function encerrarProcesso(Requisicao $requisicao, User $user): Requisicao
    {
        $this->support->sincronizarRequisicao($requisicao);
        $requisicao->refresh();

        if ($requisicao->status !== RequisicaoStatus::Emitida) {
            throw ValidationException::withMessages([
                'status' => ['O processo só pode ser encerrado quando todos os Boletins Internos estiverem emitidos ou rejeitados (estado actual: '.$requisicao->status->value.').'],
            ]);
        }

        $requisicao->update([
            'status' => RequisicaoStatus::Encerrada,
            'encerrada_em' => now(),
            'encerrada_por_id' => $user->id,
        ]);

        return $requisicao->refresh();
    }

    private function notificarIsv(BoletimAnalise $ba, Requisicao $requisicao): void
    {
        $isvIds = User::query()->whereHas('userRoles', fn ($q) => $q->where('role', AppRole::Isv->value))->pluck('id');
        foreach ($isvIds as $userId) {
            Notification::query()->create([
                'id' => (string) Str::uuid(),
                'user_id' => $userId,
                'title' => "Boletim de Análises {$ba->numero}",
                'message' => "Emitido o Boletim de Análises {$ba->numero} do processo {$requisicao->pat_numero} ({$requisicao->cliente_nome}).",
                'type' => 'info',
                'link' => "/admin/lab/processos/{$requisicao->id}",
                'read' => false,
                'created_at' => now(),
            ]);
        }
        $ba->update(['isv_notificado_em' => now()]);
    }
}

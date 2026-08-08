<?php

namespace App\Services;

use App\Models\Evaluation;
use App\Models\EvaluationHistory;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * 4ª máquina de estados do sistema (a 1ª é o `LabWorkflowService` da Onda 3,
 * a 2ª o `ProcessWorkflowService` e a 3ª o `MissionWorkflowService`).
 * Replica exactamente `mocks/handlers/avaliacoes.ts`: todas as transições
 * passam por `applyTransition`, que valida o estado de origem e grava sempre
 * uma linha em `evaluation_history`. `reabrir` é a transição especial que
 * limpa todos os carimbos de submissão/aprovação/rejeição/reconhecimento.
 */
class EvaluationWorkflowService
{
    public function submeter(Evaluation $evaluation, ?string $actorId): Evaluation
    {
        return $this->applyTransition(
            $evaluation, ['rascunho', 'rejeitada'], 'submeter', 'submetida', $actorId,
            ['submitted_at' => now(), 'rejection_reason' => null],
        );
    }

    public function aprovar(Evaluation $evaluation, ?string $actorId): Evaluation
    {
        return $this->applyTransition(
            $evaluation, ['submetida'], 'aprovar', 'aprovada', $actorId,
            ['approved_at' => now(), 'approved_by' => $actorId, 'rejection_reason' => null],
        );
    }

    public function rejeitar(Evaluation $evaluation, ?string $actorId, string $reason): Evaluation
    {
        return $this->applyTransition(
            $evaluation, ['submetida'], 'rejeitar', 'rejeitada', $actorId,
            ['rejection_reason' => $reason], $reason,
        );
    }

    public function validar(Evaluation $evaluation, ?string $actorId): Evaluation
    {
        return $this->applyTransition(
            $evaluation, ['aprovada'], 'validar', 'validada', $actorId,
            ['acknowledged_at' => now()],
        );
    }

    public function reabrir(Evaluation $evaluation, ?string $actorId): Evaluation
    {
        return $this->applyTransition(
            $evaluation, ['aprovada', 'validada', 'rejeitada'], 'reabrir', 'rascunho', $actorId,
            [
                'submitted_at' => null, 'approved_at' => null, 'approved_by' => null,
                'rejection_reason' => null, 'acknowledged_at' => null,
            ],
        );
    }

    /** @param  list<string>  $allowedFrom */
    private function applyTransition(
        Evaluation $evaluation,
        array $allowedFrom,
        string $action,
        string $toStatus,
        ?string $actorId,
        array $patch,
        ?string $comment = null,
    ): Evaluation {
        $fromStatus = $evaluation->status;

        if (! in_array($fromStatus, $allowedFrom, true)) {
            throw ValidationException::withMessages([
                'status' => ["Transição inválida a partir do estado \"{$fromStatus}\"."],
            ])->status(409);
        }

        $evaluation->update(['status' => $toStatus, ...$patch]);

        EvaluationHistory::query()->create([
            'id' => (string) Str::uuid(),
            'evaluation_id' => $evaluation->id,
            'actor_id' => $actorId && User::query()->whereKey($actorId)->exists() ? $actorId : null,
            'action' => $action,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'comment' => $comment,
            'changes' => null,
            'created_at' => now(),
        ]);

        return $evaluation->fresh();
    }
}

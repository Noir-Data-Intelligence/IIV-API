<?php

namespace App\Services;

use App\Models\ProcessEvent;
use App\Models\Processo;
use App\Models\ProcessStep;
use App\Models\ProcessType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * 2ª máquina de estados do sistema (a 1ª é o `LabWorkflowService` da Onda 3).
 * Replica exactamente `mocks/handlers/processes.ts`: ao criar um processo,
 * instancia as etapas a partir do tipo, activa a primeira e regista o evento
 * "aberto"; avançar/devolver/cancelar são as únicas transições — o cliente
 * nunca manipula `status`/`currentStepId` directamente.
 */
class ProcessWorkflowService
{
    public function gerarCodigo(): string
    {
        $ano = now()->year;
        $seq = Processo::query()->count() + 1;

        return sprintf('PRC-%d-%04d', $ano, $seq);
    }

    /** @param  array{typeId:string,title:string,description?:?string,priority:string,dueDate?:?string,requesterId:string,linkedEntityType?:?string,linkedEntityId?:?string}  $input */
    public function criar(array $input): Processo
    {
        return DB::transaction(function () use ($input) {
            $type = ProcessType::query()->findOrFail($input['typeId']);
            $now = now();

            $processo = Processo::query()->create([
                'id' => (string) Str::uuid(),
                'code' => $this->gerarCodigo(),
                'type_id' => $type->id,
                'title' => $input['title'],
                'description' => $input['description'] ?? null,
                'requester_id' => $input['requesterId'],
                'status' => 'aberto',
                'priority' => $input['priority'],
                'due_date' => $input['dueDate'] ?? null,
                'opened_at' => $now,
                'closed_at' => null,
                'current_step_id' => null,
                'linked_entity_type' => $input['linkedEntityType'] ?? null,
                'linked_entity_id' => $input['linkedEntityId'] ?? null,
                'created_at' => $now,
            ]);

            $typeSteps = $type->steps;
            $firstStepId = null;

            foreach ($typeSteps as $index => $typeStep) {
                $isFirst = $index === 0;
                $step = ProcessStep::query()->create([
                    'id' => (string) Str::uuid(),
                    'process_id' => $processo->id,
                    'type_step_id' => $typeStep->id,
                    'order_index' => $typeStep->order_index,
                    'name' => $typeStep->name,
                    'assignee_role' => $typeStep->default_role,
                    'status' => $isFirst ? 'em_curso' : 'pendente',
                    'started_at' => $isFirst ? $now : null,
                    'due_at' => $typeStep->sla_days ? $now->clone()->addDays($typeStep->sla_days) : null,
                    'completed_at' => null,
                    'notes' => null,
                    'created_at' => $now,
                ]);
                if ($isFirst) {
                    $firstStepId = $step->id;
                }
            }

            if ($firstStepId) {
                $processo->update(['current_step_id' => $firstStepId, 'status' => 'em_curso']);
            }

            $this->registarEvento($processo, $input['requesterId'] ?? null, 'aberto', array_filter([
                'title' => $processo->title,
                'source' => $input['linkedEntityType'] ?? null,
            ], fn ($v) => $v !== null));

            return $processo->fresh();
        });
    }

    /** Conclui a etapa activa e activa a próxima; sem próxima, fecha o processo. */
    public function avancar(Processo $processo, ?string $actorId, ?string $notes): Processo
    {
        $current = $this->etapaActual($processo);
        if (! $current) {
            throw ValidationException::withMessages(['step' => ['Sem etapa activa.']]);
        }

        $now = now();
        $notes = $notes ? trim($notes) : null;

        $current->update([
            'status' => 'concluida',
            'completed_at' => $now,
            'notes' => $notes ?? $current->notes,
        ]);

        $next = ProcessStep::query()
            ->where('process_id', $processo->id)
            ->where('order_index', '>', $current->order_index)
            ->where('status', '!=', 'concluida')
            ->orderBy('order_index')
            ->first();

        if ($next) {
            $next->update(['status' => 'em_curso', 'started_at' => $now]);
            $processo->update(['current_step_id' => $next->id, 'status' => 'em_curso']);
            $this->registarEvento($processo, $actorId, 'avancado', array_filter([
                'step' => $current->name,
                'next' => $next->name,
                'notes' => $notes,
            ], fn ($v) => $v !== null));
        } else {
            $processo->update(['status' => 'concluido', 'closed_at' => $now, 'current_step_id' => null]);
            $this->registarEvento($processo, $actorId, 'fechado', array_filter([
                'step' => $current->name,
                'notes' => $notes,
            ], fn ($v) => $v !== null));
        }

        return $processo->fresh();
    }

    /** Devolve o processo à etapa anterior — marca a actual como "devolvida". */
    public function devolver(Processo $processo, ?string $actorId, ?string $notes): Processo
    {
        $current = $this->etapaActual($processo);
        if (! $current) {
            throw ValidationException::withMessages(['step' => ['Sem etapa activa.']]);
        }

        $previous = ProcessStep::query()
            ->where('process_id', $processo->id)
            ->where('order_index', '<', $current->order_index)
            ->orderByDesc('order_index')
            ->first();

        if (! $previous) {
            throw ValidationException::withMessages(['step' => ['Sem etapa anterior.']]);
        }

        $now = now();
        $notes = $notes ? trim($notes) : null;

        $current->update(['status' => 'devolvida', 'notes' => $notes ?? $current->notes]);
        $previous->update(['status' => 'em_curso', 'started_at' => $now, 'completed_at' => null]);
        $processo->update(['current_step_id' => $previous->id, 'status' => 'em_curso']);

        $this->registarEvento($processo, $actorId, 'devolvido', array_filter([
            'step' => $current->name,
            'to' => $previous->name,
            'notes' => $notes,
        ], fn ($v) => $v !== null));

        return $processo->fresh();
    }

    public function cancelar(Processo $processo, ?string $actorId, ?string $notes): Processo
    {
        $now = now();
        $reason = $notes ? trim($notes) : null;

        $processo->update(['status' => 'cancelado', 'closed_at' => $now]);
        $this->registarEvento($processo, $actorId, 'cancelado', $reason ? ['reason' => $reason] : null);

        return $processo->fresh();
    }

    public function registarEvento(Processo $processo, ?string $actorId, string $eventType, ?array $payload): ProcessEvent
    {
        return ProcessEvent::query()->create([
            'id' => (string) Str::uuid(),
            'process_id' => $processo->id,
            'actor_id' => $actorId && User::query()->whereKey($actorId)->exists() ? $actorId : null,
            'event_type' => $eventType,
            'payload' => $payload,
            'created_at' => now(),
        ]);
    }

    /** Etapa apontada por `currentStepId`, ou a primeira "em_curso" (mesma regra do mock). */
    private function etapaActual(Processo $processo): ?ProcessStep
    {
        if ($processo->current_step_id) {
            $step = ProcessStep::query()->find($processo->current_step_id);
            if ($step) {
                return $step;
            }
        }

        return ProcessStep::query()
            ->where('process_id', $processo->id)
            ->where('status', 'em_curso')
            ->first();
    }
}

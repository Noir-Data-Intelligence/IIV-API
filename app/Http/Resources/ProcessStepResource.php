<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProcessStepResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'processId' => $this->process_id,
            'typeStepId' => $this->type_step_id,
            'orderIndex' => $this->order_index,
            'name' => $this->name,
            'assigneeRole' => $this->assignee_role,
            'status' => $this->status,
            'startedAt' => $this->started_at?->toISOString(),
            'dueAt' => $this->due_at?->toISOString(),
            'completedAt' => $this->completed_at?->toISOString(),
            'notes' => $this->notes,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}

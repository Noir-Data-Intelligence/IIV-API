<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProcessoResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'typeId' => $this->type_id,
            'title' => $this->title,
            'description' => $this->description,
            'requesterId' => $this->requester_id,
            'status' => $this->status,
            'priority' => $this->priority,
            'dueDate' => $this->due_date?->toDateString(),
            'openedAt' => $this->opened_at?->toISOString(),
            'closedAt' => $this->closed_at?->toISOString(),
            'currentStepId' => $this->current_step_id,
            'linkedEntityType' => $this->linked_entity_type,
            'linkedEntityId' => $this->linked_entity_id,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}

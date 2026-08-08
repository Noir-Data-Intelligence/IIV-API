<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvaluationHistoryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'evaluationId' => $this->evaluation_id,
            'actorId' => $this->actor_id,
            'actorName' => $this->actor?->full_name,
            'action' => $this->action,
            'fromStatus' => $this->from_status,
            'toStatus' => $this->to_status,
            'comment' => $this->comment,
            'changes' => $this->changes,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}

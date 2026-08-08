<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProcessEventResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'processId' => $this->process_id,
            'actorId' => $this->actor_id,
            'eventType' => $this->event_type,
            'payload' => $this->payload,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}

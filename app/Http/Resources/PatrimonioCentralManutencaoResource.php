<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatrimonioCentralManutencaoResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'assetId' => $this->patrimonio_central_id,
            'date' => $this->date?->toDateString(),
            'type' => $this->type,
            'description' => $this->description,
            'cost' => (float) $this->cost,
            'provider' => $this->provider,
            'nextDueDate' => $this->next_due_date?->toDateString(),
            'notes' => $this->notes,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}

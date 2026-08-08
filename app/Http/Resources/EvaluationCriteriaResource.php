<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvaluationCriteriaResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cycleId' => $this->cycle_id,
            'name' => $this->name,
            'weight' => (float) $this->weight,
            'description' => $this->description,
            'displayOrder' => (int) $this->display_order,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}

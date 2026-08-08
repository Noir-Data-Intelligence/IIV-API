<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TankResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'centerId' => $this->center_id,
            'code' => $this->code,
            'capacityL' => (float) $this->capacity_l,
            'currentLevelL' => (float) $this->current_level_l,
            'minLevelL' => (float) $this->min_level_l,
            'lastRefillDate' => $this->last_refill_date?->toDateString(),
            'notes' => $this->notes,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}

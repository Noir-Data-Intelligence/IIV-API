<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HarvestResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fieldId' => $this->field_id,
            'harvestDate' => $this->harvest_date?->toDateString(),
            'quantity' => (float) $this->quantity,
            'unit' => $this->unit,
            'qualityGrade' => $this->quality_grade,
            'notes' => $this->notes,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}

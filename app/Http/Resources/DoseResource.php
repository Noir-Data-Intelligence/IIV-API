<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoseResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'breederId' => $this->breeder_id,
            'tankId' => $this->tank_id,
            'collectionDate' => $this->collection_date?->toDateString(),
            'quantity' => (int) $this->quantity,
            'availableQuantity' => (int) $this->available_quantity,
            'qualityGrade' => $this->quality_grade,
            'notes' => $this->notes,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}

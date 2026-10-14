<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnimalResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'stationId' => $this->station_id,
            'tag' => $this->tag,
            'name' => $this->name,
            'species' => $this->species,
            'breed' => $this->breed,
            'sex' => $this->sex,
            'reproductiveStatus' => $this->reproductive_status,
            'birthDate' => $this->birth_date?->toDateString(),
            'motherTag' => $this->mother_tag,
            'fatherTag' => $this->father_tag,
            'status' => $this->status,
            'currentWeightKg' => $this->current_weight_kg !== null ? (float) $this->current_weight_kg : null,
            'notes' => $this->notes,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}

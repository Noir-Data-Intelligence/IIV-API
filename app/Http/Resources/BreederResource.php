<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BreederResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'centerId' => $this->center_id,
            'tag' => $this->tag,
            'name' => $this->name,
            'species' => $this->species,
            'breed' => $this->breed,
            'birthDate' => $this->birth_date?->toDateString(),
            'status' => $this->status,
            'notes' => $this->notes,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}

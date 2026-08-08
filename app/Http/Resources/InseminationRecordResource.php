<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InseminationRecordResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'animalId' => $this->animal_id,
            'doseId' => $this->dose_id,
            'technicianId' => $this->technician_id,
            'inseminationDate' => $this->insemination_date?->toDateString(),
            'result' => $this->result,
            'pregnancyConfirmedAt' => $this->pregnancy_confirmed_at?->toDateString(),
            'expectedBirthDate' => $this->expected_birth_date?->toDateString(),
            'notes' => $this->notes,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HealthRecordResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'animalId' => $this->animal_id,
            'recordType' => $this->record_type,
            'productName' => $this->product_name,
            'dosage' => $this->dosage,
            'diagnosis' => $this->diagnosis,
            'treatment' => $this->treatment,
            'veterinarian' => $this->veterinarian,
            'recordDate' => $this->record_date?->toDateString(),
            'nextDueDate' => $this->next_due_date?->toDateString(),
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}

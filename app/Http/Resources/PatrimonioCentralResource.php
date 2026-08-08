<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatrimonioCentralResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'category' => $this->category,
            'description' => $this->description,
            'location' => $this->location,
            'departmentId' => $this->department_id,
            'responsibleUser' => $this->responsible_user,
            'acquisitionDate' => $this->acquisition_date?->toDateString(),
            'acquisitionCost' => (float) $this->acquisition_cost,
            'currentValue' => $this->current_value !== null ? (float) $this->current_value : null,
            'serialNumber' => $this->serial_number,
            'status' => $this->status,
            'notes' => $this->notes,
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
        ];
    }
}

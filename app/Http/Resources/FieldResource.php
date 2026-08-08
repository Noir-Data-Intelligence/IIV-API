<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FieldResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'stationId' => $this->station_id,
            'cropId' => $this->crop_id,
            'fieldCode' => $this->field_code,
            'areaHa' => (float) $this->area_ha,
            'plantingDate' => $this->planting_date?->toDateString(),
            'expectedHarvest' => $this->expected_harvest?->toDateString(),
            'status' => $this->status,
            'notes' => $this->notes,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}

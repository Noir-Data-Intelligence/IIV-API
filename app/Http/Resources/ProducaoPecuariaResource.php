<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProducaoPecuariaResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'stationId' => $this->station_id,
            'productType' => $this->product_type,
            'productionDate' => $this->production_date?->toDateString(),
            'quantity' => (float) $this->quantity,
            'unit' => $this->unit,
            'recordedBy' => $this->recorded_by,
            'notes' => $this->notes,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}

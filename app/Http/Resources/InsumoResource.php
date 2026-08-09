<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Forma exacta de InsumoDto em Front-end/src/types/dto/insumo.ts. */
class InsumoResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'laboratoryId' => $this->laboratorio_id,
            'name' => $this->name,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'minStock' => $this->min_stock,
            'expiryDate' => $this->expiry_date?->toDateString(),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockItemResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->category,
            'sku' => $this->sku,
            'unit' => $this->unit,
            'quantity' => (float) $this->quantity,
            'minStock' => (float) $this->min_stock,
            'locationId' => $this->location_id,
            'expiryDate' => $this->expiry_date?->toDateString(),
            'supplier' => $this->supplier,
            'unitCost' => $this->unit_cost !== null ? (float) $this->unit_cost : null,
            'notes' => $this->notes,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}

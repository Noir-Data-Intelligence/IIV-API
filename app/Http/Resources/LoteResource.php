<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoteResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'batchNumber' => $this->batch_number,
            'productId' => $this->product_id,
            'quantityProduced' => $this->quantity_produced,
            'quantityDistributed' => $this->quantity_distributed,
            'productionDate' => $this->production_date?->toDateString(),
            'expiryDate' => $this->expiry_date?->toDateString(),
            'status' => $this->status?->value,
            'notes' => $this->notes,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}

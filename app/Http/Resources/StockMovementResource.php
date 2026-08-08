<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'itemId' => $this->item_id,
            'type' => $this->type,
            'quantity' => (float) $this->quantity,
            'fromLocationId' => $this->from_location_id,
            'toLocationId' => $this->to_location_id,
            'reason' => $this->reason,
            'performedBy' => $this->performed_by,
            'movementDate' => $this->movement_date?->toDateString(),
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}

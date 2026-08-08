<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanoResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'productId' => $this->product_id,
            'plannedQuantity' => $this->planned_quantity,
            'actualQuantity' => $this->actual_quantity,
            'plannedStart' => $this->planned_start?->toDateString(),
            'plannedEnd' => $this->planned_end?->toDateString(),
            'status' => $this->status?->value,
            'notes' => $this->notes,
        ];
    }
}

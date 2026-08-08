<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DistribuicaoResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'destination' => $this->destination,
            'quantity' => $this->quantity,
            'distributionDate' => $this->distribution_date?->toDateString(),
            'notes' => $this->notes,
            'batchId' => $this->batch_id,
        ];
    }
}

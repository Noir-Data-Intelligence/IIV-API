<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlertAckResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'metricKey' => $this->metric_key,
            'acknowledgedUntil' => $this->acknowledged_until?->toISOString(),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardPrefResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kpis' => $this->kpis ?? [],
            'charts' => $this->charts ?? [],
            'thresholds' => $this->thresholds ?? (object) [],
            'updatedAt' => $this->updated_at?->toISOString(),
        ];
    }
}

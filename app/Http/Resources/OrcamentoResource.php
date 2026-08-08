<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrcamentoResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'year' => (int) $this->year,
            'accountId' => $this->account_id,
            'departmentId' => $this->department_id,
            'plannedAmount' => (float) $this->planned_amount,
            'notes' => $this->notes,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}

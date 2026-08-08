<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LancamentoResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'accountId' => $this->account_id,
            'departmentId' => $this->department_id,
            'type' => $this->type,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'transactionDate' => $this->transaction_date?->toDateString(),
            'description' => $this->description,
            'reference' => $this->reference,
            'status' => $this->status,
            'notes' => $this->notes,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}

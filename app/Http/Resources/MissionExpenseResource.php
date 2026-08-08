<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MissionExpenseResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'missionId' => $this->mission_id,
            'category' => $this->category,
            'description' => $this->description,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'expenseDate' => $this->expense_date?->toDateString(),
            'receiptUrl' => $this->receipt_url,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'lineId' => $this->line_id,
            'title' => $this->title,
            'objectives' => $this->objectives,
            'status' => $this->status,
            'startDate' => $this->start_date?->toDateString(),
            'endDate' => $this->end_date?->toDateString(),
            'fundingSource' => $this->funding_source,
            'fundingAmount' => $this->funding_amount !== null ? (float) $this->funding_amount : null,
            'currency' => $this->currency,
            'partners' => $this->partners,
            'createdBy' => $this->created_by,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}

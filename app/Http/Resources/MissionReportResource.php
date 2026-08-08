<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MissionReportResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'missionId' => $this->mission_id,
            'reportDate' => $this->report_date?->toDateString(),
            'summary' => $this->summary,
            'outcomes' => $this->outcomes,
            'status' => $this->status,
            'submittedBy' => $this->submitted_by,
            'approvedBy' => $this->approved_by,
            'approvedAt' => $this->approved_at?->toISOString(),
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}

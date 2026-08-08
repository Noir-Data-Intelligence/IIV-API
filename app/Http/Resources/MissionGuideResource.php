<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MissionGuideResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'missionId' => $this->mission_id,
            'guideNumber' => $this->guide_number,
            'issueDate' => $this->issue_date?->toDateString(),
            'perDiem' => (float) $this->per_diem,
            'transport' => $this->transport,
            'notes' => $this->notes,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}

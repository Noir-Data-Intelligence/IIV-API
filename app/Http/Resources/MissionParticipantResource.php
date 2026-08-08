<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MissionParticipantResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'missionId' => $this->mission_id,
            'userId' => $this->user_id,
            'fullName' => $this->user?->full_name,
            'role' => $this->role,
            'perDiem' => (float) $this->per_diem,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RhAusenciaResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employeeId' => $this->employee_id,
            'leaveType' => $this->leave_type,
            'startDate' => $this->start_date?->toDateString(),
            'endDate' => $this->end_date?->toDateString(),
            'days' => (int) $this->days,
            'status' => $this->status,
            'reason' => $this->reason,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}

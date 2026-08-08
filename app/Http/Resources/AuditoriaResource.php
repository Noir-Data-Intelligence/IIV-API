<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditoriaResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'auditType' => $this->audit_type,
            'auditor' => $this->auditor,
            'scheduledDate' => $this->scheduled_date?->toDateString(),
            'completedDate' => $this->completed_date?->toDateString(),
            'status' => $this->status,
            'findings' => $this->findings,
            'recommendations' => $this->recommendations,
            'departmentId' => $this->department_id,
            'laboratoryId' => $this->laboratory_id,
            'departmentName' => $this->department?->name,
            'laboratoryName' => $this->laboratorio?->name,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}

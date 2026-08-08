<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NaoConformidadeResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'severity' => $this->severity,
            'status' => $this->status,
            'correctiveAction' => $this->corrective_action,
            'deadline' => $this->deadline?->toDateString(),
            'resolvedAt' => $this->resolved_at?->toISOString(),
            'departmentId' => $this->department_id,
            'auditId' => $this->audit_id,
            'departmentName' => $this->department?->name,
            'auditTitle' => $this->auditoria?->title,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}

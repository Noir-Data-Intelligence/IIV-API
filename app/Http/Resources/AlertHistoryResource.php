<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * `assignedToName`/`assignedDepartmentName` — join resolvido aqui (o
 * controller faz eager-load de `assignee`/`department`), tal como o handler
 * mock fazia manualmente (ver dashboardAlertHistory.ts `resolve()`).
 */
class AlertHistoryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'metricKey' => $this->metric_key,
            'label' => $this->label,
            'value' => (float) $this->value,
            'threshold' => (float) $this->threshold,
            'tone' => $this->tone,
            'createdAt' => $this->created_at?->toISOString(),
            'userId' => $this->user_id,
            'assignedTo' => $this->assigned_to,
            'assignedDepartmentId' => $this->assigned_department_id,
            'actionStatus' => $this->action_status,
            'actionNotes' => $this->action_notes,
            'assignedAt' => $this->assigned_at?->toISOString(),
            'resolvedAt' => $this->resolved_at?->toISOString(),
            'assignedToName' => $this->whenLoaded('assignee', fn () => $this->assignee?->full_name, null),
            'assignedDepartmentName' => $this->whenLoaded('department', fn () => $this->department?->name, null),
        ];
    }
}

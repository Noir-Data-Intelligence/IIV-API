<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Forma exacta de LogDto em Front-end/src/types/dto/log.ts. `entityType` usa
 * o nome curto da classe (`class_basename`), não o namespace completo —
 * `auditable_type`/`auditable_id` do `AuditLog` mapeiam para `entityType`/
 * `entityId`; `old_values`/`new_values` consolidam-se em `details`.
 */
class LogResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'entityType' => class_basename($this->auditable_type),
            'entityId' => $this->auditable_id,
            'details' => array_filter([
                'old' => $this->old_values,
                'new' => $this->new_values,
            ], fn ($v) => $v !== null) ?: null,
            'createdAt' => $this->created_at?->toISOString(),
            'userId' => $this->actor_id,
            // Cópia desnormalizada primeiro — sobrevive à eliminação da
            // conta (`actor_id` é nullOnDelete). Cai para a relação só em
            // registos antigos, anteriores a esta coluna.
            'userName' => $this->actor_name ?? $this->actor?->full_name,
            'ipAddress' => $this->ip_address,
        ];
    }
}

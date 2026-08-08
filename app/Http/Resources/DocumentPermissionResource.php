<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentPermissionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $department = $this->department;

        return [
            'id' => $this->id,
            'documentId' => $this->document_id,
            'role' => $this->role,
            'departmentId' => $this->department_id,
            'canEdit' => (bool) $this->can_edit,
            'department' => $department ? ['name' => $department->name] : null,
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserRoleAssignmentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'userId' => $this->id,
            'fullName' => $this->full_name,
            'roles' => array_map(fn ($role) => $role->value, $this->roleValues()),
        ];
    }
}

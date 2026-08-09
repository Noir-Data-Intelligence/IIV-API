<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Forma exacta de UserDto em Front-end/src/types/dto/user.ts — camelCase,
 * `roles` ja consolidado (nao ha endpoint separado de "papel actual").
 */
class UserResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fullName' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'roles' => array_map(fn ($role) => $role->value, $this->roleValues()),
            'departmentIds' => $this->departments->pluck('id')->all(),
            'isActive' => (bool) $this->is_active,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}

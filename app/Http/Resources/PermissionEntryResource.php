<?php

namespace App\Http\Resources;

use App\Enums\AppRole;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermissionEntryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'role' => $this->role instanceof AppRole ? $this->role->value : $this->role,
            'module' => $this->module,
            'canView' => (bool) $this->can_view,
            'canWrite' => (bool) $this->can_write,
        ];
    }
}

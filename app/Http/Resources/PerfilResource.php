<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Forma exacta de PerfilDto em Front-end/src/types/dto/perfil.ts. */
class PerfilResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fullName' => $this->full_name,
            'phone' => $this->phone,
            'avatarUrl' => $this->avatar_url,
            'updatedAt' => $this->updated_at?->toISOString(),
        ];
    }
}

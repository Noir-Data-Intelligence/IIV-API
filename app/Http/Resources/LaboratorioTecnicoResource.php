<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property User $resource utilizador membro da equipa, com `pivot->funcao` carregado.
 */
class LaboratorioTecnicoResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'userId' => $this->id,
            'fullName' => $this->full_name,
            'email' => $this->email,
            'funcao' => $this->pivot?->funcao,
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ValidacaoResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'boletimInternoId' => $this->boletim_interno_id,
            'validadorId' => $this->validador_id,
            'validadorNome' => $this->whenLoaded('validador', fn () => $this->validador?->full_name),
            'ordem' => $this->ordem,
            'tipo' => $this->tipo,
            'assinatura' => $this->assinatura,
            'observacao' => $this->observacao,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}

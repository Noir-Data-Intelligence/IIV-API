<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BoletimInternoResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amostraId' => $this->amostra_id,
            'numeroAnalise' => $this->numero_analise,
            'exames' => $this->exames,
            'entradaEm' => $this->entrada_em?->toISOString(),
            'inicioEm' => $this->inicio_em?->toISOString(),
            'conclusaoEm' => $this->conclusao_em?->toISOString(),
            'status' => $this->status?->value,
            'boletimAnalise' => new BoletimAnaliseResource($this->whenLoaded('boletimAnalise')),
        ];
    }
}

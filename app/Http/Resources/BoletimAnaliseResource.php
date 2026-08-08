<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BoletimAnaliseResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'boletimInternoId' => $this->boletim_interno_id,
            'resultado' => $this->resultado,
            'validador1Id' => $this->validador_1_id,
            'validador2Id' => $this->validador_2_id,
            'validador3Id' => $this->validador_3_id,
            'validadoEm' => $this->validado_em?->toISOString(),
            'aprovadoPorId' => $this->aprovado_por_id,
            'aprovadoEm' => $this->aprovado_em?->toISOString(),
            'comunicadoEm' => $this->comunicado_em?->toISOString(),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AmostraResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'requisicaoId' => $this->requisicao_id,
            'numero' => $this->numero,
            'tipoAmostra' => $this->tipo_amostra,
            'origemMatriz' => $this->origem_matriz,
            'pontoColheita' => $this->ponto_colheita,
            'colhidaPor' => $this->colhida_por,
            'sujeito' => $this->sujeito,
            'recebidaEm' => $this->recebida_em?->toISOString(),
            'status' => $this->status?->value,
            'boletimInterno' => new BoletimInternoResource($this->whenLoaded('boletimInterno')),
            'rejeicao' => new AmostraRejeicaoResource($this->whenLoaded('rejeicao')),
        ];
    }
}

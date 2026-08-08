<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AmostraRejeicaoResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amostraId' => $this->amostra_id,
            'criterioRejeicaoId' => $this->criterio_rejeicao_id,
            'responsavelId' => $this->responsavel_id,
            'detalhe' => $this->detalhe,
            'assinaturaResponsavel' => $this->assinatura_responsavel,
            'rejeitadaEm' => $this->rejeitada_em?->toISOString(),
        ];
    }
}

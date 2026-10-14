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
            'fase' => $this->fase,
            'laboratorioId' => $this->laboratorio_id,
            'boletimInternoId' => $this->boletim_interno_id,
            'segundoResponsavelId' => $this->segundo_responsavel_id,
            'assinaturaSegundo' => $this->assinatura_segundo,
            'origemNotificadaEm' => $this->origem_notificada_em?->toISOString(),
            'accaoTomada' => $this->accao_tomada,
        ];
    }
}

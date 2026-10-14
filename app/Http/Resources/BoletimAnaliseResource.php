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
            'numero' => $this->numero,
            'requisicaoId' => $this->requisicao_id,
            'laboratorioId' => $this->laboratorio_id,
            'conteudo' => $this->conteudo,
            'emitidoPorId' => $this->emitido_por_id,
            'emitidoEm' => $this->emitido_em?->toISOString(),
            'assinadoPorId' => $this->assinado_por_id,
            'assinaturaHash' => $this->assinatura_hash,
            'pdfPath' => $this->pdf_path,
            'entregueEm' => $this->entregue_em?->toISOString(),
            'entregueA' => $this->entregue_a,
            'canalEntrega' => $this->canal_entrega,
            'isvNotificadoEm' => $this->isv_notificado_em?->toISOString(),
            // @deprecated — campos do modelo antigo, mantidos para dados existentes.
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

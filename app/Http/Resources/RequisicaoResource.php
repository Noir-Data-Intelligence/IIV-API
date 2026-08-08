<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RequisicaoResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numero' => $this->numero,
            'laboratorioId' => $this->laboratorio_id,
            'tipoSujeito' => $this->tipo_sujeito,
            'clienteNome' => $this->cliente_nome,
            'clienteContacto' => $this->cliente_contacto,
            'veterinarioResponsavel' => $this->veterinario_responsavel,
            'dadosEpidemiologicos' => $this->dados_epidemiologicos,
            'dadosFacturacao' => $this->dados_facturacao,
            'consentimento' => (bool) $this->consentimento,
            'assinaturaCliente' => $this->assinatura_cliente,
            'createdBy' => $this->created_by,
            'createdAt' => $this->created_at?->toISOString(),
            'amostras' => AmostraResource::collection($this->whenLoaded('amostras')),
        ];
    }
}

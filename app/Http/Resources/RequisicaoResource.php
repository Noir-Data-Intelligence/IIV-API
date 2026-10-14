<?php

namespace App\Http\Resources;

use App\Models\RequisicaoExame;
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
            /** @deprecated área derivada dos exames — ver `areas`. */
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
            'status' => $this->status?->value,
            'origemTipo' => $this->origem_tipo,
            'provenienciaLocal' => (bool) $this->proveniencia_local,
            'termoEntregaNumero' => $this->termo_entrega_numero,
            'termoEntregaAssinadoPor' => $this->termo_entrega_assinado_por,
            'entreguePor' => $this->entregue_por,
            'entregueEm' => $this->entregue_em?->toISOString(),
            'recebidaPorId' => $this->recebida_por_id,
            'conservacao' => $this->conservacao,
            'colheitaEm' => $this->colheita_em?->toISOString(),
            'doencasSuspeitas' => $this->doencas_suspeitas,
            'patNumero' => $this->pat_numero,
            'processoAbertoEm' => $this->processo_aberto_em?->toISOString(),
            'encerradaEm' => $this->encerrada_em?->toISOString(),
            'encerradaPorId' => $this->encerrada_por_id,
            // Áreas de destino derivadas dos exames pedidos ("Será encaminhada para: X, Y").
            'areas' => $this->whenLoaded('exames', fn () => $this->exames
                ->map(fn (RequisicaoExame $e) => $e->laboratorio?->code)
                ->filter()->unique()->sort()->values()->all()),
            'amostras' => AmostraResource::collection($this->whenLoaded('amostras')),
            'exames' => RequisicaoExameResource::collection($this->whenLoaded('exames')),
            'boletinsInternos' => BoletimInternoResource::collection($this->whenLoaded('boletinsInternos')),
            'boletinsAnalises' => BoletimAnaliseResource::collection($this->whenLoaded('boletinsAnalises')),
        ];
    }
}

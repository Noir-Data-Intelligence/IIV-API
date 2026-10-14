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
            /** @deprecated modelo antigo (1 BI por amostra) — ver `amostras`. */
            'amostraId' => $this->amostra_id,
            'numeroAnalise' => $this->numero_analise,
            'numero' => $this->numero,
            'requisicaoId' => $this->requisicao_id,
            'laboratorioId' => $this->laboratorio_id,
            'laboratorio' => $this->whenLoaded('laboratorio', fn () => $this->laboratorio ? [
                'id' => $this->laboratorio->id,
                'code' => $this->laboratorio->code,
                'name' => $this->laboratorio->name,
                'validadorCount' => $this->laboratorio->validador_count,
                'validadoresNomeados' => $this->laboratorio->validadores_nomeados,
            ] : null),
            'requisicao' => $this->whenLoaded('requisicao', fn () => $this->requisicao ? [
                'id' => $this->requisicao->id,
                'numero' => $this->requisicao->numero,
                'patNumero' => $this->requisicao->pat_numero,
                'clienteNome' => $this->requisicao->cliente_nome,
                'origemTipo' => $this->requisicao->origem_tipo,
                'status' => $this->requisicao->status?->value,
            ] : null),
            'exames' => $this->exames,
            'entradaEm' => $this->entrada_em?->toISOString(),
            'inicioEm' => $this->inicio_em?->toISOString(),
            'conclusaoEm' => $this->conclusao_em?->toISOString(),
            'prazoEm' => $this->prazo_em?->toISOString(),
            'status' => $this->status?->value,
            'resultadoGlobal' => $this->resultado_global,
            'encaminhadoPorId' => $this->encaminhado_por_id,
            'encaminhadoEm' => $this->encaminhado_em?->toISOString(),
            'recebidoAreaPorId' => $this->recebido_area_por_id,
            'recebidoAreaEm' => $this->recebido_area_em?->toISOString(),
            'responsavelTecnicoId' => $this->responsavel_tecnico_id,
            'devolvidoPorId' => $this->devolvido_por_id,
            'devolvidoEm' => $this->devolvido_em?->toISOString(),
            'recebidoCgiscPorId' => $this->recebido_cgisc_por_id,
            'recebidoCgiscEm' => $this->recebido_cgisc_em?->toISOString(),
            'motivoCorreccao' => $this->motivo_correccao,
            'biOrigemId' => $this->bi_origem_id,
            'amostras' => AmostraResource::collection($this->whenLoaded('amostras')),
            'examesPedidos' => RequisicaoExameResource::collection($this->whenLoaded('examesPedidos')),
            'ensaios' => EnsaioResource::collection($this->whenLoaded('ensaios')),
            'validacoes' => ValidacaoResource::collection($this->whenLoaded('validacoes')),
            'boletimAnalise' => new BoletimAnaliseResource($this->whenLoaded('boletimAnalise')),
        ];
    }
}

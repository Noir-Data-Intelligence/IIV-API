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
            'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
            'colhidaPor' => $this->colhida_por,
            'sujeito' => $this->sujeito,
            'recebidaEm' => $this->recebida_em?->toISOString(),
            'status' => $this->status?->value,
            'aceiteEm' => $this->aceite_em?->toISOString(),
            'aceiteComReserva' => (bool) $this->aceite_com_reserva,
            'reservaMotivo' => $this->reserva_motivo,
            'codigoBarras' => $this->codigo_barras,
            'amostraOrigemId' => $this->amostra_origem_id,
            'localizacaoActual' => $this->localizacao_actual,
            // Estado da amostra dentro de um BI (quando carregada via pivot).
            'estadoNoBoletim' => $this->whenPivotLoaded('boletim_interno_amostras', fn () => $this->pivot->estado),
            /** @deprecated modelo antigo (1 BI por amostra). */
            'boletimInterno' => new BoletimInternoResource($this->whenLoaded('boletimInterno')),
            'rejeicao' => new AmostraRejeicaoResource($this->whenLoaded('rejeicao')),
            'rejeicoes' => AmostraRejeicaoResource::collection($this->whenLoaded('rejeicoes')),
            'conferencias' => AmostraConferenciaResource::collection($this->whenLoaded('conferencias')),
            'movimentos' => AmostraMovimentoResource::collection($this->whenLoaded('movimentos')),
        ];
    }
}

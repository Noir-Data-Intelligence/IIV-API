<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Subconjunto geo-relevante de `Amostra`, para o mapa de colheitas do
 * Observatório (Onda 11, Fase 1) — não é a `AmostraResource` completa
 * (sem boletim/rejeição, que não interessam a uma vista de mapa).
 */
class ObservatorioColheitaResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'amostraId' => $this->id,
            'numero' => $this->numero,
            'tipoAmostra' => $this->tipo_amostra,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'status' => $this->status?->value,
            'recebidaEm' => $this->recebida_em?->toISOString(),
            'requisicaoId' => $this->requisicao_id,
            'laboratorioId' => $this->requisicao?->laboratorio_id,
            'laboratorioNome' => $this->requisicao?->laboratorio?->name,
        ];
    }
}

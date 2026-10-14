<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AmostraConferenciaResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amostraId' => $this->amostra_id,
            'tecnicoId' => $this->tecnico_id,
            'tecnicoNome' => $this->whenLoaded('tecnico', fn () => $this->tecnico?->full_name),
            'ordem' => $this->ordem,
            'criterios' => [
                'identificacao' => (bool) $this->crit_identificacao,
                'quantidade' => (bool) $this->crit_quantidade,
                'conservacao' => (bool) $this->crit_conservacao,
                'macroscopica' => (bool) $this->crit_macroscopica,
                'caracteristicasFisicas' => (bool) $this->crit_caracteristicas_fisicas,
                'tempoColheita' => (bool) $this->crit_tempo_colheita,
            ],
            'conforme' => (bool) $this->conforme,
            'observacao' => $this->observacao,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}

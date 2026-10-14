<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RequisicaoExameResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'requisicaoId' => $this->requisicao_id,
            'amostraId' => $this->amostra_id,
            'exameId' => $this->exame_id,
            'laboratorioId' => $this->laboratorio_id,
            'laboratorioCode' => $this->whenLoaded('laboratorio', fn () => $this->laboratorio?->code),
            'boletimInternoId' => $this->boletim_interno_id,
            'exame' => new ExameCatalogoResource($this->whenLoaded('exame')),
        ];
    }
}

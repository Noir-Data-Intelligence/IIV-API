<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnsaioResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'boletimInternoId' => $this->boletim_interno_id,
            'amostraId' => $this->amostra_id,
            'exameId' => $this->exame_id,
            'tecnica' => $this->tecnica,
            'popCodigo' => $this->pop_codigo,
            'equipamentoId' => $this->equipamento_id,
            'loteReagente' => $this->lote_reagente,
            'executorId' => $this->executor_id,
            'executorNome' => $this->whenLoaded('executor', fn () => $this->executor?->full_name),
            'segundoIntervenienteId' => $this->segundo_interveniente_id,
            'visualizadorId' => $this->visualizador_id,
            'iniciadoEm' => $this->iniciado_em?->toISOString(),
            'concluidoEm' => $this->concluido_em?->toISOString(),
            'resultado' => $this->resultado,
            'resultadoQualitativo' => $this->resultado_qualitativo,
            'interpretacao' => $this->interpretacao,
        ];
    }
}

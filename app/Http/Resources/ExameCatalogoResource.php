<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExameCatalogoResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'laboratorioId' => $this->laboratorio_id,
            'laboratorioCode' => $this->whenLoaded('laboratorio', fn () => $this->laboratorio?->code),
            'codigo' => $this->codigo,
            'nome' => $this->nome,
            'tecnicaMetodo' => $this->tecnica_metodo,
            'popCodigo' => $this->pop_codigo,
            'tiposAmostra' => $this->tipos_amostra ?? [],
            'slaHoras' => $this->sla_horas,
            'camposResultado' => $this->campos_resultado ?? [],
            'activo' => (bool) $this->activo,
        ];
    }
}

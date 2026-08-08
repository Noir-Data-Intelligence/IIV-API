<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CriterioRejeicaoResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'laboratorioId' => $this->laboratorio_id,
            'codigo' => $this->codigo,
            'motivo' => $this->motivo,
        ];
    }
}

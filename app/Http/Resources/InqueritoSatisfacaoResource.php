<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InqueritoSatisfacaoResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'requisicaoId' => $this->requisicao_id,
            'dimensoes' => $this->dimensoes,
            'comentario' => $this->comentario,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}

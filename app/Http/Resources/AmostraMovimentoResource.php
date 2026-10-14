<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AmostraMovimentoResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amostraId' => $this->amostra_id,
            'boletimInternoId' => $this->boletim_interno_id,
            'accao' => $this->accao,
            'deLocal' => $this->de_local,
            'paraLocal' => $this->para_local,
            'userId' => $this->user_id,
            'userNome' => $this->whenLoaded('user', fn () => $this->user?->full_name),
            'observacao' => $this->observacao,
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}

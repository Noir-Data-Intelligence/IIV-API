<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Forma exacta de NoticiaDto (Front-end/src/types/dto/noticia.ts) — ao
 * contrário da maioria dos outros DTOs, este usa snake_case propositado
 * (image_path/published_at/created_at), ver comentário no DTO original.
 * NUNCA convertar para camelCase aqui.
 */
class NoticiaResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'titulo' => $this->titulo,
            'resumo' => $this->resumo,
            'conteudo' => $this->conteudo,
            'categoria' => $this->categoria,
            'image_path' => $this->image_path,
            'destaque' => (bool) $this->destaque,
            'published' => (bool) $this->published,
            'published_at' => $this->published_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}

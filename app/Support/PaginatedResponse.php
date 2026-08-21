<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Envelope `{ data, meta: { currentPage, perPage, total, lastPage } }` — a
 * forma EXACTA de `PaginatedMeta`/`Paginated<T>` em
 * Front-end/src/types/dto/paginated.ts (verificado directamente contra o
 * ficheiro, não contra a paráfrase da memória do projecto — `page`/
 * `totalPages` NÃO existem no contrato real, são `currentPage`/`lastPage`).
 * Nunca usar o paginator JSON por omissão do Laravel (snake_case, forma
 * diferente).
 *
 * Uso: `return PaginatedResponse::make($paginator, DepartmentResource::class);`
 */
final class PaginatedResponse
{
    /**
     * @template TResource of JsonResource
     *
     * @param  class-string<TResource>  $resourceClass
     * @return array{data: list<TResource>, meta: array{currentPage: int, perPage: int, total: int, lastPage: int}}
     */
    public static function make(LengthAwarePaginator $paginator, string $resourceClass): array
    {
        return [
            'data' => $resourceClass::collection(collect($paginator->items()))->resolve(),
            'meta' => [
                'currentPage' => $paginator->currentPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
                'lastPage' => $paginator->lastPage(),
            ],
        ];
    }

    private function __construct() {}
}

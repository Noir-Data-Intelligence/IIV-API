<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Envelope `{ data, meta: { total, page, perPage, totalPages } }` — a forma
 * exacta de `Paginated<T>` em Front-end/src/types/dto/paginated.ts, que
 * TODOS os hooks `use*List` do frontend esperam (ver PLANO-BACKEND-
 * LARAVEL.txt seccao 4.2). Nunca usar o paginator JSON por omissao do
 * Laravel (snake_case, forma diferente).
 *
 * Uso: `return PaginatedResponse::make($paginator, DepartmentResource::class);`
 */
final class PaginatedResponse
{
    /**
     * @param  class-string<JsonResource>  $resourceClass
     */
    public static function make(LengthAwarePaginator $paginator, string $resourceClass): array
    {
        return [
            'data' => $resourceClass::collection(collect($paginator->items()))->resolve(),
            'meta' => [
                'total' => $paginator->total(),
                'page' => $paginator->currentPage(),
                'perPage' => $paginator->perPage(),
                'totalPages' => $paginator->lastPage(),
            ],
        ];
    }

    private function __construct() {}
}

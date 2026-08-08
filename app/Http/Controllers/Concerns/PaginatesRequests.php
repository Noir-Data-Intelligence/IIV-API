<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

/**
 * O frontend envia sempre `page`/`per_page` (snake_case) como query params —
 * ver Front-end/src/services/api/departamentos.ts `toQuery()` ("snake_case
 * p/ Laravel") — mesmo com o resto do contrato em camelCase. Usar em TODO
 * controller de listagem, para nunca repetir o teste `perPage` vs `per_page`
 * nem esquecer o tecto máximo (ver PLANO-BACKEND-LARAVEL.txt secção 8: alto
 * o suficiente para relatórios, baixo o suficiente para não permitir table
 * scans descontrolados).
 */
trait PaginatesRequests
{
    protected function perPageFrom(Request $request, int $default = 20, int $max = 2000): int
    {
        return max(1, min((int) $request->query('per_page', $default), $max));
    }
}

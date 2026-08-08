<?php

namespace App\Http\Middleware;

use App\Services\PermissionMatrixService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Substitui o <RoleGuard module="..."> do frontend do lado do servidor —
 * sem isto, um utilizador autenticado mas sem permissao pode contornar o
 * RoleGuard client-side com um pedido HTTP directo (ver
 * SIG-IIV-MEMORIA-PROJETO.md seccao 15). Uso na rota:
 *
 *   Route::get('/departamentos', ...)->middleware('module:departamentos');
 *   Route::post('/departamentos', ...)->middleware('module:departamentos,write');
 *
 * Sem o segundo parametro, exige apenas leitura (can_view). Aplicar
 * 'write' a TODAS as rotas de escrita (POST/PUT/PATCH/DELETE).
 */
class EnsureModulePermission
{
    public function __construct(private readonly PermissionMatrixService $permissions) {}

    public function handle(Request $request, Closure $next, string $module, string $level = 'view'): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        $role = $user->primaryRole();

        if (! $role) {
            abort(403, 'Utilizador sem papel atribuido.');
        }

        $allowed = $level === 'write'
            ? $this->permissions->canWrite($role, $module)
            : $this->permissions->canView($role, $module);

        if (! $allowed) {
            abort(403, "Sem permissao de {$level} para o modulo {$module}.");
        }

        return $next($request);
    }
}

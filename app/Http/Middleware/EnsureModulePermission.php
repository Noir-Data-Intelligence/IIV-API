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

        $roles = $user->roleValues();

        if (empty($roles)) {
            abort(403, 'Utilizador sem papel atribuido.');
        }

        // União de TODOS os papéis do utilizador, não só o "papel principal"
        // (ver PermissionMatrixService::canViewAny — corrige achado de
        // auditoria de segurança, 2026-08-09).
        // Vários módulos alternativos separados por "|" (basta um), ex:
        // 'module:lab-area|lab-validacao,write' — usado no Laboratório, onde
        // a mesma rota serve papéis de fases diferentes.
        $allowed = false;
        foreach (explode('|', $module) as $candidate) {
            $allowed = $level === 'write'
                ? $this->permissions->canWriteAny($roles, $candidate)
                : $this->permissions->canViewAny($roles, $candidate);
            if ($allowed) {
                break;
            }
        }

        if (! $allowed) {
            abort(403, "Sem permissao de {$level} para o modulo {$module}.");
        }

        return $next($request);
    }
}

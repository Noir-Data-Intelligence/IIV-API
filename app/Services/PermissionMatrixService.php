<?php

namespace App\Services;

use App\Enums\AppRole;
use App\Enums\ModuleKey;
use App\Models\RolePermission;
use Illuminate\Support\Facades\Cache;

/**
 * Matriz de permissoes (papel -> modulos visiveis/editaveis), equivalente a
 * `role_permissions` + `ensurePermissionsLoaded()`/`DYNAMIC_MATRIX` em
 * Front-end/src/lib/permissions.ts. Cache simples (limpa em cada escrita —
 * ver RbacController) porque a matriz muda raramente e e lida em quase
 * todos os pedidos autenticados.
 */
class PermissionMatrixService
{
    private const CACHE_KEY = 'rbac.role_permissions.v1';

    /** @return array{view: list<string>, write: list<string>} */
    public function forRole(AppRole $role): array
    {
        // Admin tem sempre acesso total — safety net que nao depende da
        // tabela, tal como canView()/canWrite() no frontend.
        if ($role === AppRole::Admin) {
            return ['view' => ModuleKey::ALL, 'write' => ModuleKey::ALL];
        }

        $matrix = $this->matrix();

        return $matrix[$role->value] ?? ['view' => [], 'write' => []];
    }

    public function canView(AppRole $role, string $module): bool
    {
        return in_array($module, $this->forRole($role)['view'], true);
    }

    public function canWrite(AppRole $role, string $module): bool
    {
        return in_array($module, $this->forRole($role)['write'], true);
    }

    /**
     * Verifica a UNIÃO de todos os papéis do utilizador, não um só "papel
     * principal" — um utilizador com dois papéis nunca deve ter MENOS
     * acesso do que qualquer um dos dois lhe daria isoladamente. Corrige um
     * achado de auditoria de segurança (2026-08-09): `EnsureModulePermission`
     * decidia a partir de `User::primaryRole()` (o papel alfabeticamente
     * primeiro), o que podia negar acesso que um dos outros papéis do
     * utilizador concedia — inclusive apagar a contenção deliberada do
     * papel `isv` sempre que a ordem alfabética lhe desse prioridade a ele.
     *
     * @param  list<AppRole>  $roles
     */
    public function canViewAny(array $roles, string $module): bool
    {
        foreach ($roles as $role) {
            if ($this->canView($role, $module)) {
                return true;
            }
        }

        return false;
    }

    /** @param  list<AppRole>  $roles */
    public function canWriteAny(array $roles, string $module): bool
    {
        foreach ($roles as $role) {
            if ($this->canWrite($role, $module)) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, array{view: list<string>, write: list<string>}> */
    private function matrix(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            $matrix = [];
            foreach (AppRole::cases() as $role) {
                $matrix[$role->value] = ['view' => [], 'write' => []];
            }

            foreach (RolePermission::query()->get() as $entry) {
                /** @var AppRole $role */
                $role = $entry->role;
                if ($entry->can_view) {
                    $matrix[$role->value]['view'][] = $entry->module;
                }
                if ($entry->can_write) {
                    $matrix[$role->value]['write'][] = $entry->module;
                }
            }

            return $matrix;
        });
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Enums\AppRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\PermissionEntryResource;
use App\Http\Resources\UserRoleAssignmentResource;
use App\Models\RolePermission;
use App\Models\User;
use App\Models\UserRole;
use App\Services\PermissionMatrixService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Espelha src/services/api/rbac.ts + src/mocks/handlers/rbac.ts. A matriz
 * completa e sempre substituida no PUT (nao merge parcial) — mesmo
 * comportamento do handler mock.
 */
class RbacController extends Controller
{
    public function __construct(private readonly PermissionMatrixService $permissions) {}

    public function permissions(): AnonymousResourceCollection
    {
        return PermissionEntryResource::collection(RolePermission::query()->get());
    }

    public function updatePermissions(Request $request): Response
    {
        $validated = $request->validate([
            '*.role' => ['required', Rule::in(AppRole::values())],
            '*.module' => ['required', 'string'],
            '*.canView' => ['required', 'boolean'],
            '*.canWrite' => ['required', 'boolean'],
        ]);

        RolePermission::query()->delete();

        foreach ($validated as $entry) {
            RolePermission::query()->create([
                'id' => (string) Str::uuid(),
                'role' => $entry['role'],
                'module' => $entry['module'],
                'can_view' => $entry['canView'],
                'can_write' => $entry['canWrite'],
            ]);
        }

        $this->permissions->flush();

        return response()->noContent();
    }

    public function users(): AnonymousResourceCollection
    {
        return UserRoleAssignmentResource::collection(
            User::query()->with('userRoles')->orderBy('full_name')->get()
        );
    }

    public function addRole(Request $request, User $user): UserRoleAssignmentResource
    {
        $data = $request->validate([
            'role' => ['required', Rule::in(AppRole::values())],
        ]);

        UserRole::query()->firstOrCreate(
            ['user_id' => $user->id, 'role' => $data['role']],
            ['id' => (string) Str::uuid()],
        );

        return new UserRoleAssignmentResource($user->load('userRoles'));
    }

    public function removeRole(User $user, string $role): Response
    {
        $user->userRoles()->where('role', $role)->delete();

        return response()->noContent();
    }
}

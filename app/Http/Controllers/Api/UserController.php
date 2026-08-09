<?php

namespace App\Http\Controllers\Api;

use App\Enums\AppRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\UserDepartment;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Módulo Utilizadores — espelha `src/services/api/users.ts` (`endpoints.users`).
 * `index` devolve um array simples (sem envelope `Paginated<T>`, ver nota em
 * `users.ts`) — dataset pequeno (dezenas, não milhares de utilizadores).
 */
class UserController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = User::query()->with(['userRoles', 'departments'])->orderBy('full_name');

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role = $request->string('role')->toString()) {
            $query->whereHas('userRoles', fn ($q) => $q->where('role', $role));
        }

        if ($departmentId = $request->string('departmentId')->toString()) {
            $query->whereHas('departments', fn ($q) => $q->where('departments.id', $departmentId));
        }

        return UserResource::collection($query->get());
    }

    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $data = $request->validated();

        // Sem isto, o último admin podia despromover-se ou desactivar-se a
        // si próprio (ou a outro último admin) sem nenhum caminho de
        // recuperação pela aplicação — só `artisan tinker` no container
        // (auditoria de segurança, 2026-08-09).
        if ($this->wouldStripLastActiveAdmin($user, $data)) {
            throw ValidationException::withMessages([
                'roles' => ['Tem de restar pelo menos um administrador activo.'],
            ]);
        }

        $user->fill([
            'full_name' => $data['fullName'] ?? $user->full_name,
            'phone' => array_key_exists('phone', $data) ? $data['phone'] : $user->phone,
            'is_active' => array_key_exists('isActive', $data) ? $data['isActive'] : $user->is_active,
        ])->save();

        if (array_key_exists('roles', $data)) {
            $user->userRoles()->delete();
            foreach ($data['roles'] as $role) {
                UserRole::query()->create([
                    'id' => (string) Str::uuid(),
                    'user_id' => $user->id,
                    'role' => $role,
                ]);
            }
        }

        if (array_key_exists('departmentIds', $data)) {
            UserDepartment::query()->where('user_id', $user->id)->delete();
            foreach ($data['departmentIds'] as $departmentId) {
                UserDepartment::query()->firstOrCreate([
                    'user_id' => $user->id,
                    'department_id' => $departmentId,
                ], [
                    'id' => (string) Str::uuid(),
                ]);
            }
        }

        return new UserResource($user->fresh(['userRoles', 'departments']));
    }

    public function destroy(Request $request, User $user): Response
    {
        if ($user->id === $request->user()->id) {
            throw ValidationException::withMessages([
                'user' => ['Não pode eliminar a sua própria conta.'],
            ]);
        }

        if ($this->wouldStripLastActiveAdmin($user, ['isActive' => false])) {
            throw ValidationException::withMessages([
                'user' => ['Tem de restar pelo menos um administrador activo.'],
            ]);
        }

        $user->delete();

        return response()->noContent();
    }

    /**
     * Verdadeiro se este pedido tirasse o último `admin` activo do sistema
     * (por perda do papel ou por desactivação) — usado por `update()` e
     * `destroy()`.
     *
     * @param  array<string, mixed>  $data
     */
    private function wouldStripLastActiveAdmin(User $user, array $data): bool
    {
        $isCurrentlyActiveAdmin = $user->is_active && $user->hasRole(AppRole::Admin);
        if (! $isCurrentlyActiveAdmin) {
            return false;
        }

        $staysAdmin = array_key_exists('roles', $data)
            ? in_array(AppRole::Admin->value, $data['roles'], true)
            : true;
        $staysActive = array_key_exists('isActive', $data) ? (bool) $data['isActive'] : true;

        if ($staysAdmin && $staysActive) {
            return false;
        }

        $otherActiveAdminExists = User::query()
            ->where('id', '!=', $user->id)
            ->where('is_active', true)
            ->whereHas('userRoles', fn ($q) => $q->where('role', AppRole::Admin))
            ->exists();

        return ! $otherActiveAdminExists;
    }
}

<?php

namespace App\Http\Controllers\Api;

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

        $user->fill([
            'full_name' => $data['fullName'] ?? $user->full_name,
            'phone' => array_key_exists('phone', $data) ? $data['phone'] : $user->phone,
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

    public function destroy(User $user): Response
    {
        $user->delete();

        return response()->noContent();
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Department\DepartmentRequest;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use App\Support\PaginatedResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class DepartmentController extends Controller
{
    public function index(Request $request): array
    {
        $perPage = min((int) $request->integer('perPage', 20), 2000);

        $query = Department::query()->orderBy('name');

        if ($search = $request->string('search')->toString()) {
            $query->where('name', 'like', "%{$search}%");
        }

        return PaginatedResponse::make($query->paginate($perPage), DepartmentResource::class);
    }

    public function store(DepartmentRequest $request): DepartmentResource
    {
        $department = Department::query()->create([
            'id' => (string) Str::uuid(),
            ...$this->toColumns($request->validated()),
        ]);

        return new DepartmentResource($department);
    }

    public function show(Department $department): DepartmentResource
    {
        return new DepartmentResource($department);
    }

    public function update(DepartmentRequest $request, Department $department): DepartmentResource
    {
        $department->update($this->toColumns($request->validated()));

        return new DepartmentResource($department);
    }

    /** Mapeia o payload camelCase (parentId) para as colunas da BD (parent_id). */
    private function toColumns(array $validated): array
    {
        return [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'parent_id' => $validated['parentId'] ?? null,
        ];
    }

    public function destroy(Department $department): Response
    {
        $department->delete();

        return response()->noContent();
    }
}

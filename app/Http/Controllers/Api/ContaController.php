<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\PaginatesRequests;
use App\Http\Controllers\Controller;
use App\Http\Requests\Conta\ContaRequest;
use App\Http\Resources\ContaResource;
use App\Models\Conta;
use App\Support\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class ContaController extends Controller
{
    use PaginatesRequests;

    public function index(Request $request): array
    {
        $perPage = $this->perPageFrom($request);

        $query = Conta::query()->orderBy('code');

        if ($search = $request->string('search')->toString()) {
            $query->where(fn ($q) => $q->where('code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%"));
        }
        if ($type = $request->string('type')->toString()) {
            $query->where('type', $type);
        }
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return PaginatedResponse::make($query->paginate($perPage), ContaResource::class);
    }

    public function store(ContaRequest $request): JsonResponse
    {
        $conta = Conta::query()->create([
            'id' => (string) Str::uuid(),
            ...$this->toColumns($request->validated()),
            'created_at' => now(),
        ]);

        return (new ContaResource($conta))->response()->setStatusCode(201);
    }

    public function update(ContaRequest $request, Conta $conta): ContaResource
    {
        $conta->update($this->toColumns($request->validated()));

        return new ContaResource($conta);
    }

    public function destroy(Conta $conta): Response
    {
        $conta->delete();

        return response()->noContent();
    }

    private function toColumns(array $validated): array
    {
        return [
            'code' => $validated['code'],
            'name' => $validated['name'],
            'type' => $validated['type'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['isActive'] ?? true,
        ];
    }
}

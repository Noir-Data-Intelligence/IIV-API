<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\PaginatesRequests;
use App\Http\Controllers\Controller;
use App\Http\Requests\Produto\ProdutoRequest;
use App\Http\Resources\ProdutoResource;
use App\Models\Produto;
use App\Support\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class ProdutoController extends Controller
{
    use PaginatesRequests;

    public function index(Request $request): array
    {
        $perPage = $this->perPageFrom($request);

        $query = Produto::query()->orderBy('name');

        if ($request->has('archived')) {
            $query->where('is_archived', $request->boolean('archived'));
        }
        if ($search = $request->string('search')->toString()) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhere('product_type', 'like', "%{$search}%"));
        }

        return PaginatedResponse::make($query->paginate($perPage), ProdutoResource::class);
    }

    public function store(ProdutoRequest $request): JsonResponse
    {
        $produto = Produto::query()->create([
            'id' => (string) Str::uuid(),
            ...$this->toColumns($request->validated()),
            'created_at' => now(),
        ]);

        return (new ProdutoResource($produto))->response()->setStatusCode(201);
    }

    public function update(ProdutoRequest $request, Produto $produto): ProdutoResource
    {
        $produto->update($this->toColumns($request->validated()));

        return new ProdutoResource($produto);
    }

    public function archive(Produto $produto): ProdutoResource
    {
        $produto->update(['is_archived' => true]);

        return new ProdutoResource($produto);
    }

    public function restore(Produto $produto): ProdutoResource
    {
        $produto->update(['is_archived' => false]);

        return new ProdutoResource($produto);
    }

    public function destroy(Produto $produto): Response
    {
        $produto->delete();

        return response()->noContent();
    }

    private function toColumns(array $validated): array
    {
        return [
            'name' => $validated['name'],
            'product_type' => $validated['productType'],
            'description' => $validated['description'] ?? null,
            'unit' => $validated['unit'],
            'is_archived' => $validated['isArchived'] ?? false,
        ];
    }
}

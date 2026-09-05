<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\PaginatesRequests;
use App\Http\Controllers\Controller;
use App\Http\Requests\Plano\PlanoRequest;
use App\Http\Resources\PlanoResource;
use App\Models\Plano;
use App\Support\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class PlanoController extends Controller
{
    use PaginatesRequests;

    public function index(Request $request): array
    {
        $perPage = $this->perPageFrom($request);

        $query = Plano::query()->with('produto')->orderByDesc('planned_start');

        if ($productId = $request->string('product_id')->toString()) {
            $query->where('product_id', $productId);
        }
        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }
        if ($search = $request->string('search')->toString()) {
            $query->where(fn ($q) => $q->where('notes', 'like', "%{$search}%")
                ->orWhereHas('produto', fn ($p) => $p->where('name', 'like', "%{$search}%")));
        }

        return PaginatedResponse::make($query->paginate($perPage), PlanoResource::class);
    }

    public function store(PlanoRequest $request): JsonResponse
    {
        $plano = Plano::query()->create([
            'id' => (string) Str::uuid(),
            ...$this->toColumns($request->validated()),
        ]);

        return (new PlanoResource($plano))->response()->setStatusCode(201);
    }

    public function update(PlanoRequest $request, Plano $plano): PlanoResource
    {
        $plano->update($this->toColumns($request->validated()));

        return new PlanoResource($plano);
    }

    public function destroy(Plano $plano): Response
    {
        $plano->delete();

        return response()->noContent();
    }

    private function toColumns(array $validated): array
    {
        return [
            'product_id' => $validated['productId'],
            'planned_quantity' => $validated['plannedQuantity'],
            'actual_quantity' => $validated['actualQuantity'] ?? null,
            'planned_start' => $validated['plannedStart'],
            'planned_end' => $validated['plannedEnd'],
            'status' => $validated['status'] ?? 'planeada',
            'notes' => $validated['notes'] ?? null,
        ];
    }
}

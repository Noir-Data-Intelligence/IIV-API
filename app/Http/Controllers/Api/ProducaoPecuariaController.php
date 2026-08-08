<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\PaginatesRequests;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pecuaria\ProducaoPecuariaRequest;
use App\Http\Resources\ProducaoPecuariaResource;
use App\Models\ProducaoPecuaria;
use App\Support\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class ProducaoPecuariaController extends Controller
{
    use PaginatesRequests;

    public function index(Request $request): array
    {
        $perPage = $this->perPageFrom($request);

        $query = ProducaoPecuaria::query()->orderByDesc('production_date');

        if ($search = $request->string('search')->toString()) {
            $query->where('notes', 'like', "%{$search}%");
        }
        if ($stationId = $request->string('station_id')->toString()) {
            $query->where('station_id', $stationId);
        }
        if ($productType = $request->string('product_type')->toString()) {
            $query->where('product_type', $productType);
        }

        return PaginatedResponse::make($query->paginate($perPage), ProducaoPecuariaResource::class);
    }

    public function store(ProducaoPecuariaRequest $request): JsonResponse
    {
        $data = $request->validated();
        $producao = ProducaoPecuaria::query()->create([
            'id' => (string) Str::uuid(),
            ...$this->toColumns($data),
            'created_at' => now(),
        ]);

        return (new ProducaoPecuariaResource($producao))->response()->setStatusCode(201);
    }

    public function update(ProducaoPecuariaRequest $request, ProducaoPecuaria $producao): ProducaoPecuariaResource
    {
        $producao->update($this->toColumns($request->validated()));

        return new ProducaoPecuariaResource($producao);
    }

    public function destroy(ProducaoPecuaria $producao): Response
    {
        $producao->delete();

        return response()->noContent();
    }

    private function toColumns(array $validated): array
    {
        return [
            'station_id' => $validated['stationId'],
            'product_type' => $validated['productType'] ?? 'Outro',
            'production_date' => $validated['productionDate'] ?? now()->toDateString(),
            'quantity' => $validated['quantity'],
            'unit' => $validated['unit'] ?? 'kg',
            'recorded_by' => $validated['recordedBy'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ];
    }
}

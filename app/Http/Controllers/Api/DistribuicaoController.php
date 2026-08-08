<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\PaginatesRequests;
use App\Http\Controllers\Controller;
use App\Http\Requests\Distribuicao\DistribuicaoRequest;
use App\Http\Resources\DistribuicaoResource;
use App\Models\Distribuicao;
use App\Support\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class DistribuicaoController extends Controller
{
    use PaginatesRequests;

    public function index(Request $request): array
    {
        $perPage = $this->perPageFrom($request);

        $query = Distribuicao::query()->with('lote.produto')->orderByDesc('distribution_date');

        if ($batchId = $request->string('batch_id')->toString()) {
            $query->where('batch_id', $batchId);
        }
        if ($search = $request->string('search')->toString()) {
            $query->where(fn ($q) => $q->where('destination', 'like', "%{$search}%")
                ->orWhere('notes', 'like', "%{$search}%")
                ->orWhereHas('lote', fn ($l) => $l->where('batch_number', 'like', "%{$search}%")
                    ->orWhereHas('produto', fn ($p) => $p->where('name', 'like', "%{$search}%"))));
        }

        return PaginatedResponse::make($query->paginate($perPage), DistribuicaoResource::class);
    }

    public function store(DistribuicaoRequest $request): JsonResponse
    {
        $distribuicao = Distribuicao::query()->create([
            'id' => (string) Str::uuid(),
            ...$this->toColumns($request->validated()),
        ]);

        return (new DistribuicaoResource($distribuicao))->response()->setStatusCode(201);
    }

    public function update(DistribuicaoRequest $request, Distribuicao $distribuicao): DistribuicaoResource
    {
        $distribuicao->update($this->toColumns($request->validated()));

        return new DistribuicaoResource($distribuicao);
    }

    public function destroy(Distribuicao $distribuicao): Response
    {
        $distribuicao->delete();

        return response()->noContent();
    }

    private function toColumns(array $validated): array
    {
        return [
            'batch_id' => $validated['batchId'],
            'destination' => $validated['destination'],
            'quantity' => $validated['quantity'],
            'distribution_date' => $validated['distributionDate'],
            'notes' => $validated['notes'] ?? null,
        ];
    }
}

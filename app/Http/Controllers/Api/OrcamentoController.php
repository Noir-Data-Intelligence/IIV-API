<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\PaginatesRequests;
use App\Http\Controllers\Controller;
use App\Http\Requests\Orcamento\OrcamentoRequest;
use App\Http\Resources\OrcamentoResource;
use App\Models\Orcamento;
use App\Support\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class OrcamentoController extends Controller
{
    use PaginatesRequests;

    public function index(Request $request): array
    {
        $perPage = $this->perPageFrom($request);

        $query = Orcamento::query()->orderByDesc('year')->orderBy('account_id');

        if ($search = $request->string('search')->toString()) {
            $query->where(fn ($q) => $q->where('notes', 'like', "%{$search}%")
                ->orWhere('year', 'like', "%{$search}%"));
        }
        if ($year = $request->string('year')->toString()) {
            $query->where('year', $year);
        }
        if ($accountId = $request->string('account_id')->toString()) {
            $query->where('account_id', $accountId);
        }

        return PaginatedResponse::make($query->paginate($perPage), OrcamentoResource::class);
    }

    public function store(OrcamentoRequest $request): JsonResponse
    {
        $orcamento = Orcamento::query()->create([
            'id' => (string) Str::uuid(),
            ...$this->toColumns($request->validated()),
            'created_at' => now(),
        ]);

        return (new OrcamentoResource($orcamento))->response()->setStatusCode(201);
    }

    public function update(OrcamentoRequest $request, Orcamento $orcamento): OrcamentoResource
    {
        $orcamento->update($this->toColumns($request->validated()));

        return new OrcamentoResource($orcamento);
    }

    public function destroy(Orcamento $orcamento): Response
    {
        $orcamento->delete();

        return response()->noContent();
    }

    private function toColumns(array $validated): array
    {
        return [
            'year' => $validated['year'],
            'account_id' => $validated['accountId'],
            'department_id' => $validated['departmentId'] ?? null,
            'planned_amount' => $validated['plannedAmount'],
            'notes' => $validated['notes'] ?? null,
        ];
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\PaginatesRequests;
use App\Http\Controllers\Controller;
use App\Http\Requests\Lancamento\LancamentoRequest;
use App\Http\Resources\LancamentoResource;
use App\Models\Lancamento;
use App\Support\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class LancamentoController extends Controller
{
    use PaginatesRequests;

    public function index(Request $request): array
    {
        $perPage = $this->perPageFrom($request);

        $query = Lancamento::query()->orderByDesc('transaction_date');

        if ($search = $request->string('search')->toString()) {
            $query->where(fn ($q) => $q->where('description', 'like', "%{$search}%")
                ->orWhere('reference', 'like', "%{$search}%")
                ->orWhere('notes', 'like', "%{$search}%"));
        }
        if ($type = $request->string('type')->toString()) {
            $query->where('type', $type);
        }
        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }
        if ($accountId = $request->string('account_id')->toString()) {
            $query->where('account_id', $accountId);
        }
        if ($departmentId = $request->string('department_id')->toString()) {
            $query->where('department_id', $departmentId);
        }

        return PaginatedResponse::make($query->paginate($perPage), LancamentoResource::class);
    }

    public function store(LancamentoRequest $request): JsonResponse
    {
        $lancamento = Lancamento::query()->create([
            'id' => (string) Str::uuid(),
            ...$this->toColumns($request->validated()),
            'created_at' => now(),
        ]);

        return (new LancamentoResource($lancamento))->response()->setStatusCode(201);
    }

    public function update(LancamentoRequest $request, Lancamento $lancamento): LancamentoResource
    {
        $lancamento->update($this->toColumns($request->validated()));

        return new LancamentoResource($lancamento);
    }

    public function destroy(Lancamento $lancamento): Response
    {
        $lancamento->delete();

        return response()->noContent();
    }

    private function toColumns(array $validated): array
    {
        return [
            'account_id' => $validated['accountId'],
            'department_id' => $validated['departmentId'] ?? null,
            'type' => $validated['type'],
            'amount' => $validated['amount'],
            'currency' => $validated['currency'] ?? 'AOA',
            'transaction_date' => $validated['transactionDate'],
            'description' => $validated['description'],
            'reference' => $validated['reference'] ?? null,
            'status' => $validated['status'] ?? 'pago',
            'notes' => $validated['notes'] ?? null,
        ];
    }
}

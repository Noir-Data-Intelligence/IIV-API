<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\PaginatesRequests;
use App\Http\Controllers\Controller;
use App\Http\Requests\Lote\LoteRequest;
use App\Http\Resources\LoteResource;
use App\Models\Lote;
use App\Support\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LoteController extends Controller
{
    use PaginatesRequests;

    public function index(Request $request): array
    {
        $perPage = $this->perPageFrom($request);

        $query = Lote::query()->with('produto')->orderByDesc('production_date');

        if ($productId = $request->string('product_id')->toString()) {
            $query->where('product_id', $productId);
        }
        if ($search = $request->string('search')->toString()) {
            $query->where(fn ($q) => $q->where('batch_number', 'like', "%{$search}%")
                ->orWhere('notes', 'like', "%{$search}%")
                ->orWhereHas('produto', fn ($p) => $p->where('name', 'like', "%{$search}%")));
        }

        return PaginatedResponse::make($query->paginate($perPage), LoteResource::class);
    }

    public function store(LoteRequest $request): JsonResponse
    {
        // Geração do número e insert partilham a mesma transacção/lock (CT-010 —
        // relatório de QA: `count()+1` colidia com a constraint `unique` depois de
        // eliminar um lote a meio da sequência, e em pedidos concorrentes).
        $lote = DB::transaction(function () use ($request) {
            return Lote::query()->create([
                'id' => (string) Str::uuid(),
                'batch_number' => $this->gerarNumeroLote(),
                ...$this->toColumns($request->validated()),
                'created_at' => now(),
            ]);
        });

        return (new LoteResource($lote))->response()->setStatusCode(201);
    }

    /**
     * Sequencial por ano, único, não editável (ver auditoria funcional —
     * geração automática de identificadores). Usa o máximo existente (não a
     * contagem) para não reutilizar um número já atribuído a um lote
     * entretanto eliminado, e `lockForUpdate()` para serializar pedidos
     * concorrentes dentro da transacção de `store()`.
     */
    private function gerarNumeroLote(): string
    {
        $ano = now()->year;
        $max = DB::table('lotes')
            ->where('batch_number', 'like', "LOTE-{$ano}-%")
            ->lockForUpdate()
            ->max('batch_number');

        $proximo = $max ? ((int) substr($max, -4)) + 1 : 1;

        return sprintf('LOTE-%d-%04d', $ano, $proximo);
    }

    public function update(LoteRequest $request, Lote $lote): LoteResource
    {
        $lote->update($this->toColumns($request->validated()));

        return new LoteResource($lote);
    }

    public function destroy(Lote $lote): Response
    {
        $lote->delete();

        return response()->noContent();
    }

    private function toColumns(array $validated): array
    {
        return [
            'product_id' => $validated['productId'],
            'quantity_produced' => $validated['quantityProduced'] ?? 0,
            'quantity_distributed' => $validated['quantityDistributed'] ?? 0,
            'production_date' => $validated['productionDate'],
            'expiry_date' => $validated['expiryDate'],
            'status' => $validated['status'] ?? 'planeada',
            'notes' => $validated['notes'] ?? null,
        ];
    }
}

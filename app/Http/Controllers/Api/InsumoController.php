<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\PaginatesRequests;
use App\Http\Controllers\Controller;
use App\Http\Requests\Insumo\InsumoRequest;
use App\Http\Resources\InsumoResource;
use App\Models\Insumo;
use App\Support\PaginatedResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/** Insumos/reagentes laboratoriais — depende de `laboratorios` via `laboratoryId`. */
class InsumoController extends Controller
{
    use PaginatesRequests;

    public function index(Request $request): array
    {
        $perPage = $this->perPageFrom($request);

        $query = Insumo::query()->orderBy('name');

        if ($search = $request->string('search')->toString()) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($laboratoryId = $request->string('laboratory_id')->toString()) {
            $query->where('laboratorio_id', $laboratoryId);
        }

        return PaginatedResponse::make($query->paginate($perPage), InsumoResource::class);
    }

    public function show(Insumo $insumo): InsumoResource
    {
        return new InsumoResource($insumo);
    }

    public function store(InsumoRequest $request): InsumoResource
    {
        $insumo = Insumo::query()->create([
            'id' => (string) Str::uuid(),
            ...$this->toColumns($request->validated()),
        ]);

        return new InsumoResource($insumo);
    }

    public function update(InsumoRequest $request, Insumo $insumo): InsumoResource
    {
        $insumo->update($this->toColumns($request->validated()));

        return new InsumoResource($insumo);
    }

    public function destroy(Insumo $insumo): Response
    {
        $insumo->delete();

        return response()->noContent();
    }

    /** @param array<string, mixed> $validated */
    private function toColumns(array $validated): array
    {
        return [
            'laboratorio_id' => $validated['laboratoryId'],
            'name' => $validated['name'],
            'quantity' => $validated['quantity'],
            'unit' => $validated['unit'],
            'min_stock' => $validated['minStock'] ?? 0,
            'expiry_date' => $validated['expiryDate'] ?? null,
        ];
    }
}

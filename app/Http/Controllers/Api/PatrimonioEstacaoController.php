<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\PaginatesRequests;
use App\Http\Controllers\Controller;
use App\Http\Requests\PatrimonioEstacao\PatrimonioEstacaoManutencaoRequest;
use App\Http\Requests\PatrimonioEstacao\PatrimonioEstacaoRequest;
use App\Http\Resources\PatrimonioEstacaoManutencaoResource;
use App\Http\Resources\PatrimonioEstacaoResource;
use App\Models\PatrimonioEstacao;
use App\Models\PatrimonioEstacaoManutencao;
use App\Support\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class PatrimonioEstacaoController extends Controller
{
    use PaginatesRequests;

    public function index(Request $request): array
    {
        $perPage = $this->perPageFrom($request);

        $query = PatrimonioEstacao::query()->orderBy('code');

        if ($search = $request->string('search')->toString()) {
            $query->where(fn ($q) => $q->where('code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%")
                ->orWhere('serial_number', 'like', "%{$search}%")
                ->orWhere('location', 'like', "%{$search}%")
                ->orWhereHas('responsible', fn ($r) => $r->where('full_name', 'like', "%{$search}%")));
        }
        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }
        if ($category = $request->string('category')->toString()) {
            $query->where('category', $category);
        }
        if ($stationId = $request->string('station_id')->toString()) {
            $query->where('station_id', $stationId);
        }

        return PaginatedResponse::make($query->paginate($perPage), PatrimonioEstacaoResource::class);
    }

    public function store(PatrimonioEstacaoRequest $request): JsonResponse
    {
        $activo = PatrimonioEstacao::query()->create([
            'id' => (string) Str::uuid(),
            ...$this->toColumns($request->validated()),
        ]);

        return (new PatrimonioEstacaoResource($activo))->response()->setStatusCode(201);
    }

    public function update(PatrimonioEstacaoRequest $request, PatrimonioEstacao $patrimonioEstacao): PatrimonioEstacaoResource
    {
        $patrimonioEstacao->update($this->toColumns($request->validated()));

        return new PatrimonioEstacaoResource($patrimonioEstacao);
    }

    public function destroy(PatrimonioEstacao $patrimonioEstacao): Response
    {
        $patrimonioEstacao->delete();

        return response()->noContent();
    }

    public function manutencoesIndex(Request $request): array
    {
        $perPage = $this->perPageFrom($request);

        $query = PatrimonioEstacaoManutencao::query()->orderByDesc('date');

        if ($search = $request->string('search')->toString()) {
            $query->where(fn ($q) => $q->where('description', 'like', "%{$search}%")
                ->orWhere('provider', 'like', "%{$search}%"));
        }
        if ($assetId = $request->string('asset_id')->toString()) {
            $query->where('patrimonio_estacao_id', $assetId);
        }
        if ($type = $request->string('type')->toString()) {
            $query->where('type', $type);
        }

        return PaginatedResponse::make($query->paginate($perPage), PatrimonioEstacaoManutencaoResource::class);
    }

    public function manutencoesStore(PatrimonioEstacaoManutencaoRequest $request): JsonResponse
    {
        $manutencao = PatrimonioEstacaoManutencao::query()->create([
            'id' => (string) Str::uuid(),
            ...$this->manutencaoToColumns($request->validated()),
            'created_at' => now(),
        ]);

        return (new PatrimonioEstacaoManutencaoResource($manutencao))->response()->setStatusCode(201);
    }

    public function manutencoesUpdate(PatrimonioEstacaoManutencaoRequest $request, PatrimonioEstacaoManutencao $manutencao): PatrimonioEstacaoManutencaoResource
    {
        $manutencao->update($this->manutencaoToColumns($request->validated()));

        return new PatrimonioEstacaoManutencaoResource($manutencao);
    }

    public function manutencoesDestroy(PatrimonioEstacaoManutencao $manutencao): Response
    {
        $manutencao->delete();

        return response()->noContent();
    }

    private function toColumns(array $validated): array
    {
        return [
            'code' => $validated['code'],
            'name' => $validated['name'],
            'category' => $validated['category'],
            'description' => $validated['description'] ?? null,
            'location' => $validated['location'] ?? null,
            'station_id' => $validated['stationId'],
            'department_id' => $validated['departmentId'] ?? null,
            'responsible_user_id' => $validated['responsibleUserId'] ?? null,
            'acquisition_date' => $validated['acquisitionDate'] ?? null,
            'acquisition_cost' => $validated['acquisitionCost'] ?? 0,
            'current_value' => $validated['currentValue'] ?? null,
            'serial_number' => $validated['serialNumber'] ?? null,
            'status' => $validated['status'] ?? 'activo',
            'notes' => $validated['notes'] ?? null,
        ];
    }

    private function manutencaoToColumns(array $validated): array
    {
        return [
            'patrimonio_estacao_id' => $validated['assetId'],
            'date' => $validated['date'],
            'type' => $validated['type'],
            'description' => $validated['description'],
            'cost' => $validated['cost'] ?? 0,
            'provider' => $validated['provider'] ?? null,
            'next_due_date' => $validated['nextDueDate'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ];
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\PaginatesRequests;
use App\Http\Controllers\Controller;
use App\Http\Requests\PatrimonioCentral\PatrimonioCentralManutencaoRequest;
use App\Http\Requests\PatrimonioCentral\PatrimonioCentralRequest;
use App\Http\Resources\PatrimonioCentralManutencaoResource;
use App\Http\Resources\PatrimonioCentralResource;
use App\Models\PatrimonioCentral;
use App\Models\PatrimonioCentralManutencao;
use App\Support\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class PatrimonioCentralController extends Controller
{
    use PaginatesRequests;

    public function index(Request $request): array
    {
        $perPage = $this->perPageFrom($request);

        $query = PatrimonioCentral::query()->orderBy('code');

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

        return PaginatedResponse::make($query->paginate($perPage), PatrimonioCentralResource::class);
    }

    public function store(PatrimonioCentralRequest $request): JsonResponse
    {
        $activo = PatrimonioCentral::query()->create([
            'id' => (string) Str::uuid(),
            ...$this->toColumns($request->validated()),
        ]);

        return (new PatrimonioCentralResource($activo))->response()->setStatusCode(201);
    }

    public function update(PatrimonioCentralRequest $request, PatrimonioCentral $patrimonioCentral): PatrimonioCentralResource
    {
        $patrimonioCentral->update($this->toColumns($request->validated()));

        return new PatrimonioCentralResource($patrimonioCentral);
    }

    public function destroy(PatrimonioCentral $patrimonioCentral): Response
    {
        $patrimonioCentral->delete();

        return response()->noContent();
    }

    public function manutencoesIndex(Request $request): array
    {
        $perPage = $this->perPageFrom($request);

        $query = PatrimonioCentralManutencao::query()->orderByDesc('date');

        if ($search = $request->string('search')->toString()) {
            $query->where(fn ($q) => $q->where('description', 'like', "%{$search}%")
                ->orWhere('provider', 'like', "%{$search}%"));
        }
        if ($assetId = $request->string('asset_id')->toString()) {
            $query->where('patrimonio_central_id', $assetId);
        }
        if ($type = $request->string('type')->toString()) {
            $query->where('type', $type);
        }

        return PaginatedResponse::make($query->paginate($perPage), PatrimonioCentralManutencaoResource::class);
    }

    public function manutencoesStore(PatrimonioCentralManutencaoRequest $request): JsonResponse
    {
        $manutencao = PatrimonioCentralManutencao::query()->create([
            'id' => (string) Str::uuid(),
            ...$this->manutencaoToColumns($request->validated()),
            'created_at' => now(),
        ]);

        return (new PatrimonioCentralManutencaoResource($manutencao))->response()->setStatusCode(201);
    }

    public function manutencoesUpdate(PatrimonioCentralManutencaoRequest $request, PatrimonioCentralManutencao $manutencao): PatrimonioCentralManutencaoResource
    {
        $manutencao->update($this->manutencaoToColumns($request->validated()));

        return new PatrimonioCentralManutencaoResource($manutencao);
    }

    public function manutencoesDestroy(PatrimonioCentralManutencao $manutencao): Response
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
            'patrimonio_central_id' => $validated['assetId'],
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

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\PaginatesRequests;
use App\Http\Controllers\Controller;
use App\Http\Requests\Estacao\EstacaoRequest;
use App\Http\Resources\EstacaoResource;
use App\Models\Estacao;
use App\Support\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class EstacaoController extends Controller
{
    use PaginatesRequests;

    public function index(Request $request): array
    {
        $perPage = $this->perPageFrom($request);

        $query = Estacao::query()->orderBy('name');

        if ($search = $request->string('search')->toString()) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('location', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%"));
        }

        return PaginatedResponse::make($query->paginate($perPage), EstacaoResource::class);
    }

    public function store(EstacaoRequest $request): JsonResponse
    {
        $data = $request->validated();
        $estacao = Estacao::query()->create([
            'id' => (string) Str::uuid(),
            'name' => $data['name'],
            'station_type' => $data['stationType'] ?? 'zootecnica',
            'location' => $data['location'] ?? null,
            'description' => $data['description'] ?? null,
            'is_active' => $data['isActive'] ?? true,
            'created_at' => now(),
        ]);

        return (new EstacaoResource($estacao))->response()->setStatusCode(201);
    }

    public function update(EstacaoRequest $request, Estacao $estacao): EstacaoResource
    {
        $data = $request->validated();
        $estacao->update(array_filter([
            'name' => $data['name'] ?? null,
            'station_type' => $data['stationType'] ?? null,
            'location' => array_key_exists('location', $data) ? $data['location'] : $estacao->location,
            'description' => array_key_exists('description', $data) ? $data['description'] : $estacao->description,
            'is_active' => $data['isActive'] ?? null,
        ], fn ($v) => $v !== null));

        return new EstacaoResource($estacao);
    }

    public function destroy(Estacao $estacao): Response
    {
        $estacao->delete();

        return response()->noContent();
    }
}

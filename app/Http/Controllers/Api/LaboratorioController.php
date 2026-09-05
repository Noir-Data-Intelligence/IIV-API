<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\PaginatesRequests;
use App\Http\Controllers\Controller;
use App\Http\Requests\Laboratorio\LaboratorioRequest;
use App\Http\Resources\LaboratorioResource;
use App\Models\Laboratorio;
use App\Support\PaginatedResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class LaboratorioController extends Controller
{
    use PaginatesRequests;

    public function index(Request $request): array
    {
        $perPage = $this->perPageFrom($request);

        $query = Laboratorio::query()->orderBy('name');

        if ($search = $request->string('search')->toString()) {
            $query->where('name', 'like', "%{$search}%");
        }

        return PaginatedResponse::make($query->paginate($perPage), LaboratorioResource::class);
    }

    public function store(LaboratorioRequest $request): LaboratorioResource
    {
        $laboratorio = Laboratorio::query()->create([
            'id' => (string) Str::uuid(),
            'code' => $this->gerarCodigo(),
            ...$this->toColumns($request->validated()),
        ]);

        return new LaboratorioResource($laboratorio);
    }

    /**
     * Sequencial, único, não editável (ver auditoria funcional — geração
     * automática de identificadores). Os 9 laboratórios de referência
     * (`LaboratorioSeeder`) usam mnemónicas próprias (AP/AB/AS/...) e nunca
     * passam por aqui — isto só se aplica a novos laboratórios criados via UI.
     */
    private function gerarCodigo(): string
    {
        $seq = Laboratorio::query()->count() + 1;

        return sprintf('LAB-%03d', $seq);
    }

    public function show(Laboratorio $laboratorio): LaboratorioResource
    {
        return new LaboratorioResource($laboratorio);
    }

    public function update(LaboratorioRequest $request, Laboratorio $laboratorio): LaboratorioResource
    {
        $laboratorio->update($this->toColumns($request->validated()));

        return new LaboratorioResource($laboratorio);
    }

    public function destroy(Laboratorio $laboratorio): Response
    {
        $laboratorio->delete();

        return response()->noContent();
    }

    private function toColumns(array $validated): array
    {
        return [
            'name' => $validated['name'],
            'type' => $validated['type'] ?? null,
            'description' => $validated['description'] ?? null,
            'validador_count' => $validated['validadorCount'] ?? 2,
            'sla_horas' => $validated['slaHoras'] ?? null,
            'validadores_nomeados' => $validated['validadoresNomeados'] ?? null,
            'is_active' => $validated['isActive'] ?? true,
        ];
    }
}

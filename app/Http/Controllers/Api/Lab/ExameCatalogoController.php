<?php

namespace App\Http\Controllers\Api\Lab;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lab\ExameCatalogoRequest;
use App\Http\Resources\ExameCatalogoResource;
use App\Models\Ensaio;
use App\Models\ExameCatalogo;
use App\Models\RequisicaoExame;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/** Catálogo de exames (CRUD simples). Escrita: `lab-exames`. */
class ExameCatalogoController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = ExameCatalogo::query()->with('laboratorio')->orderBy('codigo');

        if ($laboratorioId = $request->string('laboratorio_id')->toString()) {
            $query->where('laboratorio_id', $laboratorioId);
        }
        if ($request->boolean('activos')) {
            $query->where('activo', true);
        }
        if ($search = $request->string('search')->toString()) {
            $query->where(fn ($q) => $q->where('nome', 'like', "%{$search}%")->orWhere('codigo', 'like', "%{$search}%"));
        }

        return ExameCatalogoResource::collection($query->get());
    }

    public function store(ExameCatalogoRequest $request): JsonResponse
    {
        $exame = ExameCatalogo::query()->create(['id' => (string) Str::uuid(), ...$this->toColumns($request->validated())]);

        return (new ExameCatalogoResource($exame->load('laboratorio')))->response()->setStatusCode(201);
    }

    public function update(ExameCatalogoRequest $request, ExameCatalogo $exame): ExameCatalogoResource
    {
        $exame->update($this->toColumns($request->validated()));

        return new ExameCatalogoResource($exame->load('laboratorio'));
    }

    /** Exames já usados em requisições/ensaios são desactivados em vez de apagados. */
    public function destroy(ExameCatalogo $exame): Response|ExameCatalogoResource
    {
        $emUso = RequisicaoExame::query()->where('exame_id', $exame->id)->exists()
            || Ensaio::query()->where('exame_id', $exame->id)->exists();

        if ($emUso) {
            $exame->update(['activo' => false]);

            return new ExameCatalogoResource($exame->load('laboratorio'));
        }

        $exame->delete();

        return response()->noContent();
    }

    /** @param  array<string, mixed>  $v */
    private function toColumns(array $v): array
    {
        return [
            'laboratorio_id' => $v['laboratorioId'],
            'codigo' => $v['codigo'],
            'nome' => $v['nome'],
            'tecnica_metodo' => $v['tecnicaMetodo'] ?? null,
            'pop_codigo' => $v['popCodigo'] ?? null,
            'tipos_amostra' => $v['tiposAmostra'] ?? null,
            'sla_horas' => $v['slaHoras'] ?? null,
            'campos_resultado' => $v['camposResultado'] ?? null,
            'activo' => $v['activo'] ?? true,
        ];
    }
}

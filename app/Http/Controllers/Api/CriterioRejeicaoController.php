<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CriterioRejeicao\CriterioRejeicaoRequest;
use App\Http\Resources\CriterioRejeicaoResource;
use App\Models\CriterioRejeicao;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class CriterioRejeicaoController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = CriterioRejeicao::query();

        if ($laboratorioId = $request->string('laboratorio_id')->toString()) {
            $query->where(fn ($q) => $q->where('laboratorio_id', $laboratorioId)->orWhereNull('laboratorio_id'));
        }

        return CriterioRejeicaoResource::collection($query->orderBy('codigo')->get());
    }

    public function store(CriterioRejeicaoRequest $request): JsonResponse
    {
        $data = $request->validated();

        $criterio = CriterioRejeicao::query()->create([
            'id' => (string) Str::uuid(),
            'laboratorio_id' => $data['laboratorioId'] ?? null,
            'codigo' => $data['codigo'],
            'motivo' => $data['motivo'],
        ]);

        return (new CriterioRejeicaoResource($criterio))->response()->setStatusCode(201);
    }

    public function destroy(CriterioRejeicao $criterioRejeicao): Response
    {
        $criterioRejeicao->delete();

        return response()->noContent();
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Laboratorio\LaboratorioTecnicoRequest;
use App\Http\Resources\LaboratorioTecnicoResource;
use App\Models\Laboratorio;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Equipa de Técnicos por laboratório (pedido do cliente: "gestão de
 * equipas"). Distinta de `Laboratorio.validadores_nomeados` (ver
 * `LaboratorioController`/`LabWorkflowService`) — esta é a equipa
 * operacional completa, não o subconjunto autorizado a validar.
 */
class LaboratorioTecnicoController extends Controller
{
    public function index(Laboratorio $laboratorio): AnonymousResourceCollection
    {
        return LaboratorioTecnicoResource::collection($laboratorio->tecnicos()->get());
    }

    public function store(LaboratorioTecnicoRequest $request, Laboratorio $laboratorio): JsonResponse
    {
        $validated = $request->validated();

        $laboratorio->tecnicos()->syncWithoutDetaching([
            $validated['userId'] => ['funcao' => $validated['funcao'] ?? null],
        ]);

        $user = $laboratorio->tecnicos()->where('users.id', $validated['userId'])->firstOrFail();

        return response()->json(new LaboratorioTecnicoResource($user), 201);
    }

    public function destroy(Laboratorio $laboratorio, User $user): Response
    {
        $laboratorio->tecnicos()->detach($user->id);

        return response()->noContent();
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\PaginatesRequests;
use App\Http\Controllers\Controller;
use App\Http\Resources\BoletimInternoResource;
use App\Models\BoletimInterno;
use App\Support\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @deprecated Rotas legadas `/boletins`. A leitura continua a funcionar;
 * as transições antigas (resultado/validar/comunicar) passavam o BI a
 * aprovado/comunicado sem devolução à CGISC nem emissão separada (DEF-003)
 * — respondem 410 Gone e apontam para os endpoints novos por fase.
 */
class BoletimController extends Controller
{
    use PaginatesRequests;

    public function index(Request $request): array
    {
        $perPage = $this->perPageFrom($request);

        $query = BoletimInterno::query()->with('boletimAnalise', 'laboratorio')->orderByDesc('entrada_em');

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        return PaginatedResponse::make($query->paginate($perPage), BoletimInternoResource::class);
    }

    public function show(BoletimInterno $boletim): BoletimInternoResource
    {
        return new BoletimInternoResource($boletim->load('boletimAnalise', 'laboratorio', 'amostras', 'validacoes'));
    }

    public function registarResultado(BoletimInterno $boletim): JsonResponse
    {
        return $this->retirado('PUT /api/lab/boletins-internos/{bi}/ensaios e POST /api/lab/boletins-internos/{bi}/submeter-validacao');
    }

    public function validar(BoletimInterno $boletim): JsonResponse
    {
        return $this->retirado('POST /api/lab/boletins-internos/{bi}/validar');
    }

    public function comunicar(BoletimInterno $boletim): JsonResponse
    {
        return $this->retirado('POST /api/lab/boletins-internos/{bi}/emitir (só a CGISC) e POST /api/lab/boletins-analises/{ba}/entregar');
    }

    private function retirado(string $substituto): JsonResponse
    {
        return response()->json([
            'message' => "Endpoint retirado (fluxo antigo do laboratório). Usar: {$substituto}.",
        ], 410);
    }
}

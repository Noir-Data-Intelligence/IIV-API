<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\PaginatesRequests;
use App\Http\Controllers\Controller;
use App\Http\Resources\AmostraResource;
use App\Models\Amostra;
use App\Support\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @deprecated Rotas legadas `/amostras`. A leitura continua a funcionar;
 * as acções de workflow antigas (aceitar num clique / rejeitar com uma só
 * assinatura) foram retiradas porque eram o próprio DEF-001 — respondem
 * 410 Gone e apontam para os endpoints novos da Recepção.
 */
class AmostraController extends Controller
{
    use PaginatesRequests;

    public function index(Request $request): array
    {
        $perPage = $this->perPageFrom($request);

        $query = Amostra::query()->orderByDesc('recebida_em');

        if ($requisicaoId = $request->string('requisicao_id')->toString()) {
            $query->where('requisicao_id', $requisicaoId);
        }
        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        return PaginatedResponse::make($query->paginate($perPage), AmostraResource::class);
    }

    public function show(Amostra $amostra): AmostraResource
    {
        return new AmostraResource($amostra->load('rejeicao', 'rejeicoes', 'conferencias.tecnico'));
    }

    public function aceitar(Amostra $amostra): JsonResponse
    {
        return $this->retirado('POST /api/lab/recepcao/amostras/{amostra}/conferencias (dupla conferência por 2 técnicos) e POST /api/lab/recepcao/requisicoes/{requisicao}/abrir-processo');
    }

    public function rejeitar(Amostra $amostra): JsonResponse
    {
        return $this->retirado('POST /api/lab/recepcao/amostras/{amostra}/rejeitar (Termo de Rejeição com 2 assinaturas)');
    }

    private function retirado(string $substituto): JsonResponse
    {
        return response()->json([
            'message' => "Endpoint retirado (fluxo antigo do laboratório). Usar: {$substituto}.",
        ], 410);
    }
}

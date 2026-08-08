<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\PaginatesRequests;
use App\Http\Controllers\Controller;
use App\Http\Requests\InqueritoSatisfacao\InqueritoSatisfacaoRequest;
use App\Http\Resources\InqueritoSatisfacaoResource;
use App\Models\InqueritoSatisfacao;
use App\Support\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** FM-SQ-066 — inquérito de satisfação, entidade desacoplada pós-entrega (RN). */
class InqueritoSatisfacaoController extends Controller
{
    use PaginatesRequests;

    public function index(Request $request): array
    {
        $perPage = $this->perPageFrom($request);
        $query = InqueritoSatisfacao::query()->orderByDesc('created_at');

        return PaginatedResponse::make($query->paginate($perPage), InqueritoSatisfacaoResource::class);
    }

    public function store(InqueritoSatisfacaoRequest $request): JsonResponse
    {
        $data = $request->validated();

        $inquerito = InqueritoSatisfacao::query()->create([
            'id' => (string) Str::uuid(),
            'requisicao_id' => $data['requisicaoId'],
            'dimensoes' => $data['dimensoes'],
            'comentario' => $data['comentario'] ?? null,
            'created_at' => now(),
        ]);

        return (new InqueritoSatisfacaoResource($inquerito))->response()->setStatusCode(201);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\PaginatesRequests;
use App\Http\Controllers\Controller;
use App\Http\Requests\Requisicao\RequisicaoRequest;
use App\Http\Resources\RequisicaoResource;
use App\Models\Requisicao;
use App\Services\Lab\RecepcaoService;
use App\Support\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @deprecated Rotas legadas `/requisicoes` — mantidas por compatibilidade.
 * O registo passa a delegar no RecepcaoService (mesmas regras/numeração do
 * fluxo novo); usar `/lab/recepcao/requisicoes`.
 */
class RequisicaoController extends Controller
{
    use PaginatesRequests;

    public function __construct(private readonly RecepcaoService $recepcao) {}

    public function index(Request $request): array
    {
        $perPage = $this->perPageFrom($request);

        $query = Requisicao::query()->with('amostras', 'exames.laboratorio')->orderByDesc('created_at');

        if ($laboratorioId = $request->string('laboratorio_id')->toString()) {
            $query->where(fn ($q) => $q->where('laboratorio_id', $laboratorioId)
                ->orWhereHas('exames', fn ($e) => $e->where('laboratorio_id', $laboratorioId)));
        }
        if ($search = $request->string('search')->toString()) {
            $query->where(fn ($q) => $q->where('cliente_nome', 'like', "%{$search}%")->orWhere('numero', 'like', "%{$search}%"));
        }

        return PaginatedResponse::make($query->paginate($perPage), RequisicaoResource::class);
    }

    /** Cria a requisição e o lote de amostras associado (RN "anexo para lotes"). */
    public function store(RequisicaoRequest $request): JsonResponse
    {
        $requisicao = $this->recepcao->registarRequisicao($request->validated(), $request->user());

        return (new RequisicaoResource($requisicao->load('amostras', 'exames.laboratorio')))->response()->setStatusCode(201);
    }

    public function show(Requisicao $requisicao): RequisicaoResource
    {
        return new RequisicaoResource($requisicao->load(
            'amostras.rejeicao', 'amostras.conferencias', 'exames.exame', 'exames.laboratorio',
            'boletinsInternos.laboratorio', 'boletinsAnalises',
        ));
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\PaginatesRequests;
use App\Http\Controllers\Controller;
use App\Http\Requests\Requisicao\RequisicaoRequest;
use App\Http\Resources\RequisicaoResource;
use App\Models\Amostra;
use App\Models\Requisicao;
use App\Services\LabWorkflowService;
use App\Support\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RequisicaoController extends Controller
{
    use PaginatesRequests;

    public function __construct(private readonly LabWorkflowService $workflow) {}

    public function index(Request $request): array
    {
        $perPage = $this->perPageFrom($request);

        $query = Requisicao::query()->with('amostras')->orderByDesc('created_at');

        if ($laboratorioId = $request->string('laboratorio_id')->toString()) {
            $query->where('laboratorio_id', $laboratorioId);
        }
        if ($search = $request->string('search')->toString()) {
            $query->where(fn ($q) => $q->where('cliente_nome', 'like', "%{$search}%")->orWhere('numero', 'like', "%{$search}%"));
        }

        return PaginatedResponse::make($query->paginate($perPage), RequisicaoResource::class);
    }

    /** Cria a requisição e o lote de amostras associado (RN "anexo para lotes"). */
    public function store(RequisicaoRequest $request): JsonResponse
    {
        $data = $request->validated();

        $requisicao = DB::transaction(function () use ($data, $request) {
            $requisicao = Requisicao::query()->create([
                'id' => (string) Str::uuid(),
                'numero' => $this->workflow->gerarNumeroRequisicao(),
                'laboratorio_id' => $data['laboratorioId'],
                'tipo_sujeito' => $data['tipoSujeito'],
                'cliente_nome' => $data['clienteNome'],
                'cliente_contacto' => $data['clienteContacto'] ?? null,
                'veterinario_responsavel' => $data['veterinarioResponsavel'] ?? null,
                'dados_epidemiologicos' => $data['dadosEpidemiologicos'] ?? null,
                'dados_facturacao' => $data['dadosFacturacao'] ?? null,
                'consentimento' => (bool) $data['consentimento'],
                'assinatura_cliente' => $data['assinaturaCliente'],
                'created_by' => $request->user()->id,
            ]);

            foreach ($data['amostras'] as $indice => $amostraData) {
                Amostra::query()->create([
                    'id' => (string) Str::uuid(),
                    'requisicao_id' => $requisicao->id,
                    'numero' => $this->workflow->gerarNumeroAmostra($requisicao, $indice + 1),
                    'tipo_amostra' => $amostraData['tipoAmostra'],
                    'origem_matriz' => $amostraData['origemMatriz'] ?? null,
                    'ponto_colheita' => $amostraData['pontoColheita'] ?? null,
                    'latitude' => $amostraData['latitude'] ?? null,
                    'longitude' => $amostraData['longitude'] ?? null,
                    'colhida_por' => $amostraData['colhidaPor'] ?? null,
                    'sujeito' => $amostraData['sujeito'] ?? null,
                    'recebida_em' => now(),
                ]);
            }

            return $requisicao;
        });

        return (new RequisicaoResource($requisicao->load('amostras')))->response()->setStatusCode(201);
    }

    public function show(Requisicao $requisicao): RequisicaoResource
    {
        return new RequisicaoResource($requisicao->load('amostras.boletimInterno.boletimAnalise', 'amostras.rejeicao'));
    }
}

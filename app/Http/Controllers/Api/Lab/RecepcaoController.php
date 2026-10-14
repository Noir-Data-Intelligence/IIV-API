<?php

namespace App\Http\Controllers\Api\Lab;

use App\Enums\AppRole;
use App\Http\Controllers\Concerns\PaginatesRequests;
use App\Http\Controllers\Controller;
use App\Http\Requests\Lab\RegistarRequisicaoRequest;
use App\Http\Resources\AmostraConferenciaResource;
use App\Http\Resources\AmostraResource;
use App\Http\Resources\RequisicaoResource;
use App\Models\Amostra;
use App\Models\AmostraConferencia;
use App\Models\CriterioRejeicao;
use App\Models\Requisicao;
use App\Models\User;
use App\Services\Lab\RecepcaoService;
use App\Support\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Fase 1 — Recepção (CGISC). Módulo RBAC `lab-recepcao`.
 */
class RecepcaoController extends Controller
{
    use PaginatesRequests;

    public function __construct(private readonly RecepcaoService $recepcao) {}

    /** Requisições por estado (separadores da página Recepção). */
    public function index(Request $request): array
    {
        $query = Requisicao::query()
            ->with(['amostras.conferencias', 'exames.laboratorio', 'boletinsInternos.laboratorio'])
            ->orderByDesc('created_at');

        if ($status = $request->string('status')->toString()) {
            $query->whereIn('status', explode(',', $status));
        }
        if ($search = $request->string('search')->toString()) {
            $query->where(fn ($q) => $q->where('cliente_nome', 'like', "%{$search}%")
                ->orWhere('numero', 'like', "%{$search}%")
                ->orWhere('pat_numero', 'like', "%{$search}%"));
        }

        return PaginatedResponse::make($query->paginate($this->perPageFrom($request)), RequisicaoResource::class);
    }

    public function store(RegistarRequisicaoRequest $request): JsonResponse
    {
        $requisicao = $this->recepcao->registarRequisicao($request->validated(), $request->user());

        return (new RequisicaoResource($this->carregar($requisicao)))->response()->setStatusCode(201);
    }

    public function show(Requisicao $requisicao): RequisicaoResource
    {
        return new RequisicaoResource($this->carregar($requisicao));
    }

    public function conferir(Request $request, Amostra $amostra): JsonResponse
    {
        $criterios = array_keys(AmostraConferencia::CRITERIOS);
        $data = $request->validate([
            'criterios' => ['required', 'array'],
            ...collect($criterios)->mapWithKeys(fn ($c) => ["criterios.{$c}" => ['required', 'boolean']])->all(),
            'observacao' => ['nullable', 'string'],
        ]);

        $conferencia = $this->recepcao->conferir($amostra, $request->user(), $data['criterios'], $data['observacao'] ?? null);

        return (new AmostraConferenciaResource($conferencia->load('tecnico')))->response()->setStatusCode(201);
    }

    public function aceitarComReserva(Request $request, Amostra $amostra): AmostraResource
    {
        $data = $request->validate(['motivo' => ['required', 'string', 'max:1000']]);
        $this->recepcao->aceitarComReserva($amostra, $request->user(), $data['motivo']);

        return new AmostraResource($amostra->refresh()->load('conferencias.tecnico'));
    }

    /** Termo de Rejeição na recepção — 2 assinaturas de responsáveis diferentes. */
    public function rejeitar(Request $request, Amostra $amostra): AmostraResource
    {
        $data = $request->validate([
            'criterioRejeicaoId' => ['required', 'uuid', 'exists:criterios_rejeicao,id'],
            'assinaturaResponsavel' => ['required', 'string'],
            'segundoResponsavelId' => ['required', 'uuid', 'exists:users,id'],
            'assinaturaSegundo' => ['required', 'string'],
            'detalhe' => ['nullable', 'string'],
            'accaoTomada' => ['nullable', 'string'],
        ]);

        $this->recepcao->rejeitar(
            $amostra,
            $request->user(),
            $data['assinaturaResponsavel'],
            User::query()->findOrFail($data['segundoResponsavelId']),
            $data['assinaturaSegundo'],
            CriterioRejeicao::query()->findOrFail($data['criterioRejeicaoId']),
            $data['detalhe'] ?? null,
            $data['accaoTomada'] ?? null,
        );

        return new AmostraResource($amostra->refresh()->load('rejeicao', 'conferencias.tecnico'));
    }

    public function abrirProcesso(Request $request, Requisicao $requisicao): RequisicaoResource
    {
        $this->recepcao->abrirProcesso($requisicao, $request->user());

        return new RequisicaoResource($this->carregar($requisicao->refresh()));
    }

    public function etiquetas(Requisicao $requisicao): JsonResponse
    {
        return response()->json($this->recepcao->etiquetas($requisicao));
    }

    /**
     * Colegas do laboratório (para escolher o 2º responsável de um Termo de
     * Rejeição, sem exigir o módulo `utilizadores`). Só id + nome + papéis.
     */
    public function colegas(): JsonResponse
    {
        $papeis = [AppRole::Recepcionista, AppRole::Tecnico, AppRole::DirectorLaboratorio, AppRole::ResponsavelQualidade, AppRole::Admin];

        $users = User::query()
            ->where('is_active', true)
            ->whereHas('userRoles', fn ($q) => $q->whereIn('role', array_map(fn (AppRole $r) => $r->value, $papeis)))
            ->with('userRoles')
            ->orderBy('full_name')
            ->get();

        return response()->json(['data' => $users->map(fn (User $u) => [
            'id' => $u->id,
            'fullName' => $u->full_name,
            'roles' => array_map(fn (AppRole $r) => $r->value, $u->roleValues()),
        ])->values()]);
    }

    private function carregar(Requisicao $requisicao): Requisicao
    {
        return $requisicao->load([
            'amostras.conferencias.tecnico', 'amostras.rejeicoes',
            'exames.exame', 'exames.laboratorio',
            'boletinsInternos.laboratorio', 'boletinsAnalises',
        ]);
    }
}

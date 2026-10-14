<?php

namespace App\Http\Controllers\Api\Lab;

use App\Enums\AppRole;
use App\Enums\BoletimInternoStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Lab\RegistarEnsaioRequest;
use App\Http\Resources\BoletimInternoResource;
use App\Http\Resources\EnsaioResource;
use App\Http\Resources\LaboratorioResource;
use App\Models\BoletimInterno;
use App\Models\CriterioRejeicao;
use App\Models\Laboratorio;
use App\Models\User;
use App\Services\Lab\AreaWorkflowService;
use App\Services\Lab\LabSupport;
use App\Services\PermissionMatrixService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * Fase 2 — trabalho na área. Módulo RBAC `lab-area` (validação também
 * `lab-validacao`), sempre com escopo por equipa (BoletimInternoPolicy).
 */
class AreaController extends Controller
{
    public function __construct(
        private readonly AreaWorkflowService $area,
        private readonly LabSupport $support,
        private readonly PermissionMatrixService $permissions,
    ) {}

    /** Áreas do utilizador (equipa) — supervisão vê todas. */
    public function minhasAreas(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $query = Laboratorio::query()->where('is_active', true)->orderBy('code');
        if (! $this->support->supervisiona($user) && ! $user->hasRole(AppRole::Diretor)) {
            $query->whereHas('tecnicos', fn ($q) => $q->where('users.id', $user->id));
        }

        return LaboratorioResource::collection($query->get());
    }

    /** Fila da área: "Por receber" / "Em análise" / "Em validação" / "Validados". */
    public function fila(Request $request, Laboratorio $laboratorio): JsonResponse
    {
        Gate::authorize('verFilaArea', [BoletimInterno::class, $laboratorio]);

        $bis = BoletimInterno::query()
            ->where('laboratorio_id', $laboratorio->id)
            ->whereNotIn('status', [BoletimInternoStatus::Gerado->value])
            ->with(['requisicao', 'amostras', 'laboratorio', 'validacoes.validador'])
            ->orderBy('prazo_em')
            ->orderBy('entrada_em')
            ->get();

        $grupo = fn (array $estados) => BoletimInternoResource::collection(
            $bis->filter(fn (BoletimInterno $b) => in_array($b->status, $estados, true))->values()
        );

        return response()->json([
            'laboratorio' => new LaboratorioResource($laboratorio),
            'porReceber' => $grupo([BoletimInternoStatus::EmTransito]),
            'emAnalise' => $grupo([BoletimInternoStatus::RecebidoArea, BoletimInternoStatus::EmAnalise]),
            'emValidacao' => $grupo([BoletimInternoStatus::ResultadoRegistado, BoletimInternoStatus::EmValidacao]),
            'validados' => $grupo([BoletimInternoStatus::Validado]),
        ]);
    }

    public function show(BoletimInterno $boletimInterno): BoletimInternoResource
    {
        Gate::authorize('view', $boletimInterno);

        return new BoletimInternoResource($this->carregar($boletimInterno));
    }

    public function iniciarEnsaio(Request $request, BoletimInterno $boletimInterno): BoletimInternoResource
    {
        Gate::authorize('operarNaArea', $boletimInterno);
        $this->area->iniciarEnsaio($boletimInterno, $request->user());

        return new BoletimInternoResource($this->carregar($boletimInterno->refresh()));
    }

    public function registarEnsaio(RegistarEnsaioRequest $request, BoletimInterno $boletimInterno): EnsaioResource
    {
        Gate::authorize('operarNaArea', $boletimInterno);
        $ensaio = $this->area->registarEnsaio($boletimInterno, $request->user(), $request->validated());

        return new EnsaioResource($ensaio->load('executor'));
    }

    public function rejeitarAmostras(Request $request, BoletimInterno $boletimInterno): BoletimInternoResource
    {
        Gate::authorize('operarNaArea', $boletimInterno);
        $data = $request->validate([
            'amostraIds' => ['required', 'array', 'min:1'],
            'amostraIds.*' => ['uuid'],
            'criterioRejeicaoId' => ['required', 'uuid', 'exists:criterios_rejeicao,id'],
            'assinaturaResponsavel' => ['required', 'string'],
            'segundoResponsavelId' => ['required', 'uuid', 'exists:users,id'],
            'assinaturaSegundo' => ['required', 'string'],
            'detalhe' => ['nullable', 'string'],
        ]);

        $this->area->rejeitarAmostras(
            $boletimInterno,
            $request->user(),
            $data['assinaturaResponsavel'],
            User::query()->findOrFail($data['segundoResponsavelId']),
            $data['assinaturaSegundo'],
            CriterioRejeicao::query()->findOrFail($data['criterioRejeicaoId']),
            $data['amostraIds'],
            $data['detalhe'] ?? null,
        );

        return new BoletimInternoResource($this->carregar($boletimInterno->refresh()));
    }

    public function submeterValidacao(Request $request, BoletimInterno $boletimInterno): BoletimInternoResource
    {
        Gate::authorize('operarNaArea', $boletimInterno);
        $data = $request->validate(['resultadoGlobal' => ['nullable', 'in:positivo,negativo,inconclusivo']]);
        $this->area->submeterValidacao($boletimInterno, $request->user(), $data['resultadoGlobal'] ?? null);

        return new BoletimInternoResource($this->carregar($boletimInterno->refresh()));
    }

    /**
     * Validação: Responsável de Qualidade/Director (`lab-validacao`) em
     * qualquer área; um técnico (`lab-area`) só na sua área — e nunca
     * sobre um ensaio que executou (regra no serviço).
     */
    public function validar(Request $request, BoletimInterno $boletimInterno): BoletimInternoResource
    {
        $user = $request->user();
        if (! $this->permissions->canWriteAny($user->roleValues(), 'lab-validacao')) {
            Gate::authorize('operarNaArea', $boletimInterno);
        }
        $data = $request->validate([
            'assinatura' => ['nullable', 'string'],
            'observacao' => ['nullable', 'string'],
            'tipo' => ['nullable', 'in:segundo,terceiro,hierarquica,qualidade'],
        ]);

        $this->area->validar($boletimInterno, $user, $data['assinatura'] ?? null, $data['observacao'] ?? null, $data['tipo'] ?? null);

        return new BoletimInternoResource($this->carregar($boletimInterno->refresh()));
    }

    public function devolver(Request $request, BoletimInterno $boletimInterno): BoletimInternoResource
    {
        Gate::authorize('operarNaArea', $boletimInterno);
        $this->area->devolverACgisc($boletimInterno, $request->user());

        return new BoletimInternoResource($this->carregar($boletimInterno->refresh()));
    }

    private function carregar(BoletimInterno $bi): BoletimInterno
    {
        return $bi->load([
            'laboratorio', 'requisicao', 'amostras', 'examesPedidos.exame',
            'ensaios.executor', 'validacoes.validador', 'boletimAnalise',
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\PaginatesRequests;
use App\Http\Controllers\Controller;
use App\Http\Requests\Amostra\RejeitarAmostraRequest;
use App\Http\Resources\AmostraResource;
use App\Models\Amostra;
use App\Models\CriterioRejeicao;
use App\Services\LabWorkflowService;
use App\Support\PaginatedResponse;
use Illuminate\Http\Request;

class AmostraController extends Controller
{
    use PaginatesRequests;

    public function __construct(private readonly LabWorkflowService $workflow) {}

    public function index(Request $request): array
    {
        $perPage = $this->perPageFrom($request);

        $query = Amostra::query()->with('boletimInterno')->orderByDesc('recebida_em');

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
        return new AmostraResource($amostra->load('boletimInterno.boletimAnalise', 'rejeicao'));
    }

    public function aceitar(Amostra $amostra): AmostraResource
    {
        $this->workflow->aceitarAmostra($amostra);

        return new AmostraResource($amostra->refresh()->load('boletimInterno'));
    }

    public function rejeitar(RejeitarAmostraRequest $request, Amostra $amostra): AmostraResource
    {
        $data = $request->validated();
        $criterio = CriterioRejeicao::query()->findOrFail($data['criterioRejeicaoId']);

        $this->workflow->rejeitarAmostra(
            $amostra,
            $criterio,
            $request->user(),
            $data['assinaturaResponsavel'],
            $data['detalhe'] ?? null,
        );

        return new AmostraResource($amostra->refresh()->load('rejeicao'));
    }
}

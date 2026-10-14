<?php

namespace App\Http\Controllers\Api\Lab;

use App\Http\Controllers\Concerns\PaginatesRequests;
use App\Http\Controllers\Controller;
use App\Http\Resources\BoletimInternoResource;
use App\Models\BoletimInterno;
use App\Models\Laboratorio;
use App\Models\Requisicao;
use App\Services\Lab\EncaminhamentoService;
use App\Support\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * Circulação física do BI: encaminhar (CGISC, `lab-recepcao`), receber na
 * área e reencaminhar (equipa da área, `lab-area` + BoletimInternoPolicy).
 */
class EncaminhamentoController extends Controller
{
    use PaginatesRequests;

    public function __construct(private readonly EncaminhamentoService $encaminhamento) {}

    /** Quadro de encaminhamentos (todas as áreas) — filtros: laboratorio (code), status. */
    public function index(Request $request): array
    {
        $query = BoletimInterno::query()
            ->whereNotNull('requisicao_id')
            ->with(['laboratorio', 'requisicao', 'amostras'])
            ->orderByDesc('entrada_em');

        if ($code = $request->string('laboratorio')->toString()) {
            $query->whereHas('laboratorio', fn ($q) => $q->where('code', $code));
        }
        if ($status = $request->string('status')->toString()) {
            $query->whereIn('status', explode(',', $status));
        }
        if ($search = $request->string('search')->toString()) {
            $query->where('numero', 'like', "%{$search}%");
        }

        return PaginatedResponse::make($query->paginate($this->perPageFrom($request, 50)), BoletimInternoResource::class);
    }

    public function encaminhar(Request $request, BoletimInterno $boletimInterno): BoletimInternoResource
    {
        $bi = $this->encaminhamento->encaminhar($boletimInterno, $request->user());

        return new BoletimInternoResource($bi->load('laboratorio', 'requisicao', 'amostras'));
    }

    public function encaminharTodos(Request $request, Requisicao $requisicao): AnonymousResourceCollection
    {
        $bis = $this->encaminhamento->encaminharTodos($requisicao, $request->user());

        return BoletimInternoResource::collection($bis->each->load('laboratorio'));
    }

    public function receberNaArea(Request $request, BoletimInterno $boletimInterno): BoletimInternoResource
    {
        Gate::authorize('operarNaArea', $boletimInterno);
        $bi = $this->encaminhamento->receberNaArea($boletimInterno, $request->user());

        return new BoletimInternoResource($bi->load('laboratorio', 'requisicao', 'amostras'));
    }

    public function reencaminhar(Request $request, BoletimInterno $boletimInterno): JsonResponse
    {
        Gate::authorize('operarNaArea', $boletimInterno);
        $data = $request->validate([
            'laboratorioId' => ['required', 'uuid', 'exists:laboratorios,id'],
            'exameIds' => ['required', 'array', 'min:1'],
            'exameIds.*' => ['uuid'],
            'amostraIds' => ['nullable', 'array'],
            'amostraIds.*' => ['uuid'],
            'motivo' => ['required', 'string', 'max:1000'],
            'duplicar' => ['sometimes', 'boolean'],
        ]);

        $novo = $this->encaminhamento->reencaminhar(
            $boletimInterno,
            $request->user(),
            Laboratorio::query()->findOrFail($data['laboratorioId']),
            $data['exameIds'],
            $data['amostraIds'] ?? null,
            $data['motivo'],
            (bool) ($data['duplicar'] ?? false),
        );

        return (new BoletimInternoResource($novo->load('laboratorio', 'requisicao', 'amostras')))->response()->setStatusCode(201);
    }
}

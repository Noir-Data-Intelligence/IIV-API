<?php

namespace App\Http\Controllers\Api\Lab;

use App\Enums\BoletimInternoStatus;
use App\Enums\RequisicaoStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\BoletimAnaliseResource;
use App\Http\Resources\BoletimInternoResource;
use App\Http\Resources\RequisicaoResource;
use App\Models\BoletimAnalise;
use App\Models\BoletimInterno;
use App\Models\Requisicao;
use App\Services\Lab\EmissaoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Fase 3 — entrega de resultados. Só a CGISC (`lab-emissao`) emite o
 * Boletim de Análises oficial — um Técnico recebe 403 no middleware.
 */
class EmissaoController extends Controller
{
    public function __construct(private readonly EmissaoService $emissao) {}

    /** Fila da CGISC: BI devolvidos, recebidos (a emitir), emitidos e processos por encerrar. */
    public function index(): JsonResponse
    {
        $bis = BoletimInterno::query()
            ->whereIn('status', [BoletimInternoStatus::Devolvido->value, BoletimInternoStatus::RecebidoCgisc->value, BoletimInternoStatus::Emitido->value])
            ->with(['laboratorio', 'requisicao', 'amostras', 'validacoes.validador', 'boletimAnalise'])
            ->orderByDesc('updated_at')
            ->limit(500)
            ->get();

        $grupo = fn (BoletimInternoStatus $s) => BoletimInternoResource::collection($bis->filter(fn (BoletimInterno $b) => $b->status === $s)->values());

        $porEncerrar = Requisicao::query()
            ->where('status', RequisicaoStatus::Emitida->value)
            ->with(['boletinsInternos.laboratorio', 'boletinsAnalises'])
            ->orderByDesc('updated_at')
            ->get();

        return response()->json([
            'devolvidos' => $grupo(BoletimInternoStatus::Devolvido),
            'aEmitir' => $grupo(BoletimInternoStatus::RecebidoCgisc),
            'emitidos' => $grupo(BoletimInternoStatus::Emitido),
            'porEncerrar' => RequisicaoResource::collection($porEncerrar),
        ]);
    }

    public function receberDevolucao(Request $request, BoletimInterno $boletimInterno): BoletimInternoResource
    {
        $bi = $this->emissao->receberDevolucao($boletimInterno, $request->user());

        return new BoletimInternoResource($bi->load('laboratorio', 'requisicao'));
    }

    public function devolverParaCorreccao(Request $request, BoletimInterno $boletimInterno): BoletimInternoResource
    {
        $data = $request->validate(['motivo' => ['required', 'string', 'max:2000']]);
        $bi = $this->emissao->devolverParaCorreccao($boletimInterno, $request->user(), $data['motivo']);

        return new BoletimInternoResource($bi->load('laboratorio', 'requisicao'));
    }

    public function emitir(Request $request, BoletimInterno $boletimInterno): JsonResponse
    {
        $ba = $this->emissao->emitirBoletimAnalises($boletimInterno, $request->user());

        return (new BoletimAnaliseResource($ba))->response()->setStatusCode(201);
    }

    public function showBoletimAnalise(BoletimAnalise $boletimAnalise): BoletimAnaliseResource
    {
        return new BoletimAnaliseResource($boletimAnalise);
    }

    public function entregar(Request $request, BoletimAnalise $boletimAnalise): BoletimAnaliseResource
    {
        $data = $request->validate([
            'entregueA' => ['required', 'string', 'max:255'],
            'canal' => ['required', 'in:presencial,email,isv,portal'],
            // STUB: não há módulo de pagamentos — confirmação manual no payload.
            'pagamentoConfirmado' => ['sometimes', 'boolean'],
        ]);

        $ba = $this->emissao->entregar($boletimAnalise, $request->user(), $data['entregueA'], $data['canal'], (bool) ($data['pagamentoConfirmado'] ?? false));

        return new BoletimAnaliseResource($ba);
    }

    public function encerrar(Request $request, Requisicao $requisicao): RequisicaoResource
    {
        $req = $this->emissao->encerrarProcesso($requisicao, $request->user());

        return new RequisicaoResource($req->load('boletinsInternos.laboratorio', 'boletinsAnalises'));
    }
}

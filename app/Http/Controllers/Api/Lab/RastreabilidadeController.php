<?php

namespace App\Http\Controllers\Api\Lab;

use App\Http\Controllers\Controller;
use App\Http\Resources\AmostraMovimentoResource;
use App\Http\Resources\RequisicaoResource;
use App\Models\Amostra;
use App\Models\AmostraMovimento;
use App\Models\Requisicao;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** Consulta (só leitura) do processo laboratorial e da rastreabilidade das amostras. */
class RastreabilidadeController extends Controller
{
    public function movimentos(Amostra $amostra): AnonymousResourceCollection
    {
        return AmostraMovimentoResource::collection(
            $amostra->movimentos()->with('user')->get()
        );
    }

    /** Processo completo + timeline de movimentos de todas as amostras. */
    public function processo(Requisicao $requisicao): JsonResponse
    {
        $requisicao->load([
            'amostras.conferencias.tecnico', 'amostras.rejeicoes',
            'exames.exame', 'exames.laboratorio',
            'boletinsInternos.laboratorio', 'boletinsInternos.validacoes.validador',
            'boletinsInternos.ensaios.executor', 'boletinsInternos.amostras', 'boletinsAnalises',
        ]);

        $movimentos = AmostraMovimento::query()
            ->whereIn('amostra_id', $requisicao->amostras->pluck('id'))
            ->with('user')
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'requisicao' => new RequisicaoResource($requisicao),
            'movimentos' => AmostraMovimentoResource::collection($movimentos),
        ]);
    }
}

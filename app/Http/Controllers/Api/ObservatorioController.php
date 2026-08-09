<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EstacaoResource;
use App\Http\Resources\ObservatorioColheitaResource;
use App\Models\Amostra;
use App\Models\Estacao;
use Illuminate\Http\JsonResponse;

/**
 * Observatório Veterinário Nacional (Onda 11) — só a Fase 1 recomendada
 * pela RDA §8.4 (mapeamento georreferenciado das estações + georreferenciação
 * das colheitas). Sem contrato de frontend a espelhar — construído a partir
 * dos documentos de requisitos (REQ-005 RF-067/069, RDA §8.2), não de um
 * mock existente. Fases 2 (indicadores sanitários territoriais) e 3
 * (zonas/focos, vigilância em tempo real, aprofundamento ISV) ficam
 * deliberadamente por construir — ver SIG-IIV-MEMORIA-PROJETO.md secção 15.
 */
class ObservatorioController extends Controller
{
    /** Dados de mapa: estações e colheitas com coordenadas registadas. */
    public function mapa(): JsonResponse
    {
        $estacoes = Estacao::query()
            ->whereNotNull('latitude')->whereNotNull('longitude')
            ->orderBy('name')->get();

        $colheitas = Amostra::query()
            ->whereNotNull('latitude')->whereNotNull('longitude')
            ->with('requisicao.laboratorio')
            ->orderByDesc('recebida_em')
            ->get();

        return response()->json([
            'estacoes' => EstacaoResource::collection($estacoes),
            'colheitas' => ObservatorioColheitaResource::collection($colheitas),
        ]);
    }
}

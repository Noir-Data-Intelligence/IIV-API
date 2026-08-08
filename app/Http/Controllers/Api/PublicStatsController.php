<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * Resumo publico agregado (`LiveStats.tsx`) — nao e uma tabela propria (ver
 * SIG-IIV-MEMORIA-PROJETO.md seccao 15). `anosExperiencia` calcula-se
 * dinamicamente a partir do ano de fundacao do IIV (1965). Os restantes
 * campos (produtosPortfolio/dosesProduzidas/estacoesRegionais) dependerao
 * de agregacoes reais sobre `produtos`/`lotes`/`estacoes` a partir das
 * Ondas 4/9 — por agora valores de referencia fixos (mesmos do mock),
 * cacheados ~1h tal como o plano prevê.
 */
class PublicStatsController extends Controller
{
    private const ANO_FUNDACAO_IIV = 1965;

    public function show(): JsonResponse
    {
        $stats = Cache::remember('public.stats', 3600, function () {
            return [
                'anosExperiencia' => now()->year - self::ANO_FUNDACAO_IIV,
                'produtosPortfolio' => 4,
                'dosesProduzidas' => 27000,
                'estacoesRegionais' => 4,
            ];
        });

        return response()->json($stats);
    }
}

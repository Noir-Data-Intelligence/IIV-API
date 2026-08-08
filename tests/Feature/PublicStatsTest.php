<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_nao_requer_autenticacao_e_devolve_estrutura_esperada(): void
    {
        $response = $this->getJson('/api/public/stats');

        $response->assertOk()->assertJsonStructure([
            'anosExperiencia', 'produtosPortfolio', 'dosesProduzidas', 'estacoesRegionais',
        ]);
    }

    public function test_anos_experiencia_e_calculado_a_partir_da_fundacao_em_1965(): void
    {
        $response = $this->getJson('/api/public/stats');

        $response->assertJsonPath('anosExperiencia', now()->year - 1965);
    }
}

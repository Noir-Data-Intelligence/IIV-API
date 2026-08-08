<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardPrefTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_requer_autenticacao(): void
    {
        $this->getJson('/api/dashboard-prefs/me')->assertUnauthorized();
    }

    public function test_show_cria_preferencias_vazias_no_primeiro_acesso(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/dashboard-prefs/me');

        $response->assertOk()
            ->assertJsonPath('kpis', [])
            ->assertJsonPath('charts', [])
            ->assertJsonPath('thresholds', []);

        $this->assertDatabaseCount('dashboard_prefs', 1);
    }

    public function test_update_grava_as_escolhas_do_utilizador(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->putJson('/api/dashboard-prefs/me', [
            'kpis' => ['amostrasHoje', 'taxaRejeicao'],
            'charts' => ['analisesPorMes'],
            'thresholds' => ['taxaRejeicao' => 5],
        ]);

        $response->assertOk()->assertJsonPath('kpis', ['amostrasHoje', 'taxaRejeicao']);

        $this->assertDatabaseCount('dashboard_prefs', 1);
    }

    public function test_update_e_scoped_ao_utilizador_autenticado(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $this->actingAs($userA)->putJson('/api/dashboard-prefs/me', ['kpis' => ['x']])->assertOk();
        $this->actingAs($userB)->getJson('/api/dashboard-prefs/me')->assertOk()->assertJsonPath('kpis', []);
    }
}

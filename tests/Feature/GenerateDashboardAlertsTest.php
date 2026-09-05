<?php

namespace Tests\Feature;

use App\Models\DashboardAlertHistory;
use App\Models\Insumo;
use App\Models\Laboratorio;
use App\Models\Lote;
use App\Models\NaoConformidade;
use App\Models\Produto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenerateDashboardAlertsTest extends TestCase
{
    use RefreshDatabase;

    public function test_gera_alerta_para_lote_ja_expirado_sem_qualquer_sessao_de_utilizador(): void
    {
        // Regressao central da auditoria: um lote expirado nunca ficava
        // registado no historico de alertas porque a geracao so acontecia
        // no browser, e `isExpiringSoon` deixa de contar um lote assim que
        // ele passa de "a expirar" para "expirado".
        $produto = Produto::factory()->create();
        $lote = Lote::factory()->create([
            'product_id' => $produto->id,
            'expiry_date' => now()->subDays(5)->toDateString(),
        ]);

        $this->artisan('alerts:generate')->assertExitCode(0);

        $this->assertDatabaseHas('dashboard_alert_history', [
            'metric_key' => 'batchesExpired',
            'entity_type' => 'lote',
            'entity_id' => $lote->id,
            'origem' => 'sistema',
            'user_id' => null,
        ]);
    }

    public function test_gera_alerta_para_lote_a_expirar_em_30_dias(): void
    {
        $produto = Produto::factory()->create();
        $lote = Lote::factory()->create([
            'product_id' => $produto->id,
            'expiry_date' => now()->addDays(10)->toDateString(),
        ]);

        $this->artisan('alerts:generate');

        $this->assertDatabaseHas('dashboard_alert_history', [
            'metric_key' => 'expiringSoon',
            'entity_id' => $lote->id,
        ]);
    }

    public function test_nao_duplica_alerta_para_o_mesmo_lote_em_execucoes_sucessivas(): void
    {
        $produto = Produto::factory()->create();
        $lote = Lote::factory()->create([
            'product_id' => $produto->id,
            'expiry_date' => now()->subDay()->toDateString(),
        ]);

        $this->artisan('alerts:generate');
        $this->artisan('alerts:generate');

        $this->assertSame(
            1,
            DashboardAlertHistory::query()->where('entity_id', $lote->id)->count(),
        );
    }

    public function test_gera_alerta_de_stock_critico_para_insumo_abaixo_do_minimo(): void
    {
        $laboratorio = Laboratorio::factory()->create();
        $insumo = Insumo::factory()->create([
            'laboratorio_id' => $laboratorio->id,
            'quantity' => 1,
            'min_stock' => 10,
        ]);

        $this->artisan('alerts:generate');

        $this->assertDatabaseHas('dashboard_alert_history', [
            'metric_key' => 'lowStock',
            'entity_type' => 'insumo',
            'entity_id' => $insumo->id,
        ]);
    }

    public function test_gera_alerta_para_nao_conformidade_aberta(): void
    {
        $nc = NaoConformidade::factory()->create(['status' => 'aberta']);

        $this->artisan('alerts:generate');

        $this->assertDatabaseHas('dashboard_alert_history', [
            'metric_key' => 'ncOpen',
            'entity_type' => 'nao_conformidade',
            'entity_id' => $nc->id,
        ]);
    }
}

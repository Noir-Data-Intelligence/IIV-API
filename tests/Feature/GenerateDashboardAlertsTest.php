<?php

namespace Tests\Feature;

use App\Enums\BoletimStatus;
use App\Models\Amostra;
use App\Models\BoletimInterno;
use App\Models\DashboardAlertHistory;
use App\Models\InseminationRecord;
use App\Models\Insumo;
use App\Models\Laboratorio;
use App\Models\Lote;
use App\Models\NaoConformidade;
use App\Models\Produto;
use App\Models\Requisicao;
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

    public function test_gera_alerta_para_inseminacao_com_parto_previsto_a_aproximar_se(): void
    {
        // Auditoria funcional, seccao 16 (Inseminacoes): "gerar alerta quando a
        // data prevista do parto se aproximar".
        $registo = InseminationRecord::factory()->create([
            'expected_birth_date' => now()->addDays(3)->toDateString(),
            'result' => 'pendente',
        ]);

        $this->artisan('alerts:generate')->assertExitCode(0);

        $this->assertDatabaseHas('dashboard_alert_history', [
            'metric_key' => 'birthsUpcoming',
            'entity_type' => 'inseminacao',
            'entity_id' => $registo->id,
            'origem' => 'sistema',
            'user_id' => null,
        ]);
    }

    public function test_nao_gera_alerta_de_parto_fora_da_janela_de_7_dias_nem_para_inseminacao_falhada(): void
    {
        $distante = InseminationRecord::factory()->create([
            'expected_birth_date' => now()->addDays(30)->toDateString(),
            'result' => 'pendente',
        ]);
        $passado = InseminationRecord::factory()->create([
            'expected_birth_date' => now()->subDay()->toDateString(),
            'result' => 'pendente',
        ]);
        $falhada = InseminationRecord::factory()->create([
            'expected_birth_date' => now()->addDays(2)->toDateString(),
            'result' => 'falhou',
        ]);

        $this->artisan('alerts:generate');

        foreach ([$distante, $passado, $falhada] as $registo) {
            $this->assertDatabaseMissing('dashboard_alert_history', [
                'metric_key' => 'birthsUpcoming',
                'entity_id' => $registo->id,
            ]);
        }
    }

    private function criarBoletimEmCurso(Laboratorio $laboratorio, \DateTimeInterface|string $entradaEm): BoletimInterno
    {
        $requisicao = Requisicao::factory()->create(['laboratorio_id' => $laboratorio->id]);
        $amostra = Amostra::factory()->create(['requisicao_id' => $requisicao->id]);

        return BoletimInterno::factory()->create([
            'amostra_id' => $amostra->id,
            'entrada_em' => $entradaEm,
            'status' => BoletimStatus::EmAnalise,
        ]);
    }

    public function test_gera_alerta_de_quebra_sla_para_boletim_alem_do_prazo_configurado(): void
    {
        $laboratorio = Laboratorio::factory()->create([
            'sla_rule_type' => 'horas_desde_entrada',
            'sla_horas' => 24,
            'sla_active' => true,
            'sla_severity' => 'destructive',
        ]);
        $boletim = $this->criarBoletimEmCurso($laboratorio, now()->subHours(30));

        $this->artisan('alerts:generate')->assertExitCode(0);

        $this->assertDatabaseHas('dashboard_alert_history', [
            'metric_key' => 'slaBreach',
            'entity_type' => 'boletim_interno',
            'entity_id' => $boletim->id,
            'tone' => 'destructive',
            'origem' => 'sistema',
        ]);
    }

    public function test_nao_gera_alerta_sla_dentro_do_prazo(): void
    {
        $laboratorio = Laboratorio::factory()->create([
            'sla_rule_type' => 'horas_desde_entrada',
            'sla_horas' => 24,
            'sla_active' => true,
        ]);
        $boletim = $this->criarBoletimEmCurso($laboratorio, now()->subHours(2));

        $this->artisan('alerts:generate');

        $this->assertDatabaseMissing('dashboard_alert_history', [
            'metric_key' => 'slaBreach',
            'entity_id' => $boletim->id,
        ]);
    }

    public function test_nao_gera_alerta_sla_para_laboratorio_com_sla_inactivo(): void
    {
        $laboratorio = Laboratorio::factory()->create([
            'sla_rule_type' => 'horas_desde_entrada',
            'sla_horas' => 24,
            'sla_active' => false,
        ]);
        $boletim = $this->criarBoletimEmCurso($laboratorio, now()->subHours(48));

        $this->artisan('alerts:generate');

        $this->assertDatabaseMissing('dashboard_alert_history', [
            'metric_key' => 'slaBreach',
            'entity_id' => $boletim->id,
        ]);
    }

    public function test_nao_duplica_alerta_sla_em_execucoes_sucessivas(): void
    {
        $laboratorio = Laboratorio::factory()->create([
            'sla_rule_type' => 'dias_uteis',
            'sla_dias' => 5,
            'sla_active' => true,
        ]);
        $boletim = $this->criarBoletimEmCurso($laboratorio, now()->subDays(6));

        $this->artisan('alerts:generate');
        $this->artisan('alerts:generate');

        $this->assertSame(
            1,
            DashboardAlertHistory::query()->where('metric_key', 'slaBreach')->where('entity_id', $boletim->id)->count(),
        );
    }
}

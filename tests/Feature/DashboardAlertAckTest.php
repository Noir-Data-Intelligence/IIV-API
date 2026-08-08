<?php

namespace Tests\Feature;

use App\Models\DashboardAlertAck;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DashboardAlertAckTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_requer_autenticacao(): void
    {
        $this->getJson('/api/dashboard-alert-acks')->assertUnauthorized();
    }

    public function test_index_so_devolve_acks_nao_expirados_do_utilizador(): void
    {
        $user = User::factory()->create();

        DashboardAlertAck::query()->create([
            'id' => (string) Str::uuid(), 'user_id' => $user->id, 'metric_key' => 'activo',
            'acknowledged_until' => now()->addHour(),
        ]);
        DashboardAlertAck::query()->create([
            'id' => (string) Str::uuid(), 'user_id' => $user->id, 'metric_key' => 'expirado',
            'acknowledged_until' => now()->subHour(),
        ]);

        $response = $this->actingAs($user)->getJson('/api/dashboard-alert-acks');

        $response->assertOk();
        $this->assertCount(1, $response->json());
        $this->assertSame('activo', $response->json('0.metricKey'));
    }

    public function test_store_cria_snooze_por_horas(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/dashboard-alert-acks', [
            'metricKey' => 'taxaRejeicao',
            'hours' => 24,
        ]);

        $response->assertCreated()->assertJsonPath('metricKey', 'taxaRejeicao');
        $this->assertDatabaseCount('dashboard_alert_acks', 1);
    }

    public function test_store_repetido_faz_upsert_sem_duplicar_nem_trocar_o_id(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/dashboard-alert-acks', ['metricKey' => 'x', 'hours' => 1])->assertCreated();
        $first = DashboardAlertAck::query()->where('metric_key', 'x')->first();

        $this->actingAs($user)->postJson('/api/dashboard-alert-acks', ['metricKey' => 'x', 'hours' => 48])->assertCreated();
        $second = DashboardAlertAck::query()->where('metric_key', 'x')->first();

        $this->assertDatabaseCount('dashboard_alert_acks', 1);
        $this->assertSame($first->id, $second->id);
    }

    public function test_destroy_reactiva_o_alerta(): void
    {
        $user = User::factory()->create();
        DashboardAlertAck::query()->create([
            'id' => (string) Str::uuid(), 'user_id' => $user->id, 'metric_key' => 'x',
            'acknowledged_until' => now()->addHour(),
        ]);

        $this->actingAs($user)->deleteJson('/api/dashboard-alert-acks/x')->assertNoContent();
        $this->assertDatabaseCount('dashboard_alert_acks', 0);
    }
}

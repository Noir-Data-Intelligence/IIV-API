<?php

namespace Tests\Feature;

use App\Enums\AppRole;
use App\Models\DashboardAlertHistory;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DashboardAlertHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        UserRole::query()->create(['id' => (string) Str::uuid(), 'user_id' => $user->id, 'role' => AppRole::Admin]);

        return $user;
    }

    public function test_store_requer_autenticacao(): void
    {
        $this->postJson('/api/dashboard-alert-history', ['metricKey' => 'x'])->assertUnauthorized();
    }

    public function test_store_cria_registo_pendente(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/dashboard-alert-history', [
            'metricKey' => 'taxaRejeicao',
            'label' => 'Taxa de rejeição',
            'value' => 12.5,
            'threshold' => 5,
            'tone' => 'critical',
        ]);

        $response->assertCreated()->assertJsonPath('actionStatus', 'pendente');
    }

    public function test_store_repetido_na_mesma_hora_e_ignorado_por_debounce(): void
    {
        $user = User::factory()->create();
        $payload = ['metricKey' => 'x', 'label' => 'X', 'value' => 1, 'threshold' => 1, 'tone' => 'warning'];

        $this->actingAs($user)->postJson('/api/dashboard-alert-history', $payload)->assertCreated();
        $this->actingAs($user)->postJson('/api/dashboard-alert-history', $payload)->assertOk();

        $this->assertDatabaseCount('dashboard_alert_history', 1);
    }

    public function test_update_requer_permissao_de_escrita_de_historico_alertas(): void
    {
        // 'historico-alertas' e visivel a todos os papeis (RolePermissionSeeder),
        // mas nenhum papel nao-admin tem permissao de escrita — so o admin
        // pode atribuir/resolver alertas (ver rota `module:historico-alertas,write`).
        $tecnico = User::factory()->create();
        UserRole::query()->create(['id' => (string) Str::uuid(), 'user_id' => $tecnico->id, 'role' => AppRole::Tecnico]);
        $row = DashboardAlertHistory::factory()->create(['user_id' => $tecnico->id]);

        $this->actingAs($tecnico)->putJson("/api/dashboard-alert-history/{$row->id}", ['actionStatus' => 'em_curso'])
            ->assertForbidden();
    }

    public function test_index_devolve_envelope_paginado(): void
    {
        $admin = $this->admin();
        DashboardAlertHistory::factory()->count(3)->create([
            'user_id' => $admin->id, 'created_at' => now()->subHours(2),
        ]);

        $this->actingAs($admin)->getJson('/api/dashboard-alert-history')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta' => ['currentPage', 'perPage', 'total', 'lastPage']]);
    }

    public function test_update_atribui_registo_e_grava_assigned_at_na_primeira_vez(): void
    {
        $admin = $this->admin();
        $assignee = User::factory()->create();
        $row = DashboardAlertHistory::factory()->create(['user_id' => $admin->id]);

        $response = $this->actingAs($admin)->putJson("/api/dashboard-alert-history/{$row->id}", [
            'assignedTo' => $assignee->id,
        ]);

        $response->assertOk();
        $this->assertNotNull($response->json('assignedAt'));
    }

    public function test_update_para_resolvido_grava_resolved_at_e_reabrir_limpa(): void
    {
        $admin = $this->admin();
        $row = DashboardAlertHistory::factory()->create(['user_id' => $admin->id]);

        $resolved = $this->actingAs($admin)->putJson("/api/dashboard-alert-history/{$row->id}", [
            'actionStatus' => 'resolvido',
        ]);
        $resolved->assertOk();
        $this->assertNotNull($resolved->json('resolvedAt'));

        $reopened = $this->actingAs($admin)->putJson("/api/dashboard-alert-history/{$row->id}", [
            'actionStatus' => 'em_curso',
        ]);
        $reopened->assertOk();
        $this->assertNull($reopened->json('resolvedAt'));
    }

    public function test_clear_remove_apenas_os_registos_do_utilizador_autenticado(): void
    {
        $admin = $this->admin();
        $other = User::factory()->create();

        DashboardAlertHistory::factory()->create(['user_id' => $admin->id]);
        DashboardAlertHistory::factory()->create(['user_id' => $other->id]);

        $this->actingAs($admin)->deleteJson('/api/dashboard-alert-history')->assertNoContent();

        $this->assertSame(0, DashboardAlertHistory::query()->where('user_id', $admin->id)->count());
        $this->assertSame(1, DashboardAlertHistory::query()->where('user_id', $other->id)->count());
    }
}

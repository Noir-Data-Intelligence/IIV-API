<?php

namespace Tests\Feature;

use App\Enums\AppRole;
use App\Models\Missao;
use App\Models\MissionExpense;
use App\Models\MissionGuide;
use App\Models\MissionParticipant;
use App\Models\MissionReport;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MissaoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function comPapel(AppRole $role): User
    {
        $user = User::factory()->create();
        UserRole::query()->create(['id' => (string) Str::uuid(), 'user_id' => $user->id, 'role' => $role]);

        return $user;
    }

    public function test_store_nasce_planeada_e_stats_conta_por_estado(): void
    {
        $user = $this->comPapel(AppRole::GestorRh);

        $missao = $this->actingAs($user)->postJson('/api/missoes', [
            'title' => 'Visita à Estação do Huambo', 'destination' => 'Huambo',
            'startDate' => '2026-09-01', 'endDate' => '2026-09-05', 'budget' => 200000,
        ])->assertCreated()->json();

        $this->assertSame('planeada', $missao['status']);
        $this->assertSame('AOA', $missao['currency']);

        $this->actingAs($user)->getJson('/api/missoes/stats')->assertOk()
            ->assertJsonPath('total', 1)->assertJsonPath('pendentes', 1);
    }

    public function test_workflow_submeter_aprovar(): void
    {
        $missao = Missao::factory()->create(['status' => 'planeada']);
        $user = $this->comPapel(AppRole::GestorRh);

        $this->actingAs($user)->postJson("/api/missoes/{$missao->id}/submeter")
            ->assertOk()->assertJsonPath('status', 'submetida');

        $this->actingAs($user)->postJson("/api/missoes/{$missao->id}/aprovar")
            ->assertOk()->assertJsonPath('status', 'aprovada');
    }

    public function test_rejeitar_devolve_a_planeada_e_transicao_invalida_devolve_409(): void
    {
        $missao = Missao::factory()->create(['status' => 'submetida']);
        $user = $this->comPapel(AppRole::GestorRh);

        $this->actingAs($user)->postJson("/api/missoes/{$missao->id}/rejeitar")
            ->assertOk()->assertJsonPath('status', 'planeada');

        // já não está "submetida" — aprovar agora é uma transição inválida.
        $this->actingAs($user)->postJson("/api/missoes/{$missao->id}/aprovar")
            ->assertStatus(409);
    }

    public function test_participantes_guia_despesas_e_relatorio(): void
    {
        $missao = Missao::factory()->create();
        $participante = User::factory()->create();
        $user = $this->comPapel(AppRole::GestorRh);

        $this->actingAs($user)->postJson("/api/missoes/{$missao->id}/participantes", [
            'userId' => $participante->id, 'role' => 'chefe_missao',
        ])->assertCreated()->assertJsonPath('fullName', $participante->full_name);

        $this->actingAs($user)->getJson("/api/missoes/{$missao->id}/guia")
            ->assertOk()->assertContent('null');

        $guia = $this->actingAs($user)->putJson("/api/missoes/{$missao->id}/guia", [
            'guideNumber' => 'GM-0001', 'issueDate' => '2026-09-01',
        ])->assertOk()->json();
        $this->assertSame('GM-0001', $guia['guideNumber']);

        // upsert: um segundo PUT actualiza o mesmo registo, não cria outro.
        $this->actingAs($user)->putJson("/api/missoes/{$missao->id}/guia", [
            'guideNumber' => 'GM-0002', 'issueDate' => '2026-09-01',
        ])->assertOk();
        $this->assertSame(1, MissionGuide::query()->where('mission_id', $missao->id)->count());

        $this->actingAs($user)->postJson("/api/missoes/{$missao->id}/despesas", [
            'category' => 'transporte', 'amount' => 15000, 'expenseDate' => '2026-09-02',
        ])->assertCreated();

        $relatorio = $this->actingAs($user)->putJson("/api/missoes/{$missao->id}/relatorio", [
            'reportDate' => '2026-09-06', 'status' => 'submetido',
        ])->assertOk()->json();
        $this->assertSame('submetido', $relatorio['status']);

        $aprovado = $this->actingAs($user)->postJson("/api/missoes/{$missao->id}/relatorio/aprovar")
            ->assertOk()->json();
        $this->assertSame('aprovado', $aprovado['status']);
        $this->assertNotNull($aprovado['approvedAt']);
    }

    public function test_destroy_missao_arrasta_participantes_despesas_guia_e_relatorio_em_cascata(): void
    {
        $missao = Missao::factory()->create();
        $participante = MissionParticipant::factory()->create(['mission_id' => $missao->id]);
        $despesa = MissionExpense::factory()->create(['mission_id' => $missao->id]);
        $guia = MissionGuide::factory()->create(['mission_id' => $missao->id]);
        $relatorio = MissionReport::factory()->create(['mission_id' => $missao->id]);
        $user = $this->comPapel(AppRole::GestorRh);

        $this->actingAs($user)->deleteJson("/api/missoes/{$missao->id}")->assertNoContent();

        $this->assertDatabaseMissing('missoes', ['id' => $missao->id]);
        $this->assertDatabaseMissing('mission_participants', ['id' => $participante->id]);
        $this->assertDatabaseMissing('mission_expenses', ['id' => $despesa->id]);
        $this->assertDatabaseMissing('mission_guides', ['id' => $guia->id]);
        $this->assertDatabaseMissing('mission_reports', ['id' => $relatorio->id]);
    }

    public function test_store_sem_permissao_devolve_403(): void
    {
        $user = $this->comPapel(AppRole::Isv);

        $this->actingAs($user)->postJson('/api/missoes', [
            'title' => 'X', 'destination' => 'X', 'startDate' => '2026-01-01', 'endDate' => '2026-01-02',
        ])->assertForbidden();
    }
}

<?php

namespace Tests\Feature;

use App\Enums\AppRole;
use App\Models\Evaluation;
use App\Models\EvaluationCriteria;
use App\Models\EvaluationCycle;
use App\Models\EvaluationHistory;
use App\Models\EvaluationScore;
use App\Models\RhTransversalColaborador;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AvaliacaoTest extends TestCase
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

    public function test_store_avaliacao_nasce_rascunho_e_regista_historico(): void
    {
        $cycle = EvaluationCycle::factory()->create();
        $colaborador = RhTransversalColaborador::factory()->create();
        $user = $this->comPapel(AppRole::Admin);

        $evaluation = $this->actingAs($user)->postJson('/api/avaliacoes', [
            'cycleId' => $cycle->id, 'employeeId' => $colaborador->id,
        ])->assertCreated()->json();

        $this->assertSame('rascunho', $evaluation['status']);
        $this->assertSame($colaborador->full_name, $evaluation['employeeName']);

        $history = $this->actingAs($user)->getJson("/api/avaliacoes/{$evaluation['id']}/historico")->assertOk()->json();
        $this->assertCount(1, $history);
        $this->assertSame('criou', $history[0]['action']);
    }

    public function test_workflow_completo_submeter_aprovar_validar(): void
    {
        $evaluation = Evaluation::factory()->create(['status' => 'rascunho']);
        $user = $this->comPapel(AppRole::Admin);

        $this->actingAs($user)->postJson("/api/avaliacoes/{$evaluation->id}/submeter")
            ->assertOk()->assertJsonPath('status', 'submetida');

        $this->actingAs($user)->postJson("/api/avaliacoes/{$evaluation->id}/aprovar")
            ->assertOk()->assertJsonPath('status', 'aprovada');

        $this->actingAs($user)->postJson("/api/avaliacoes/{$evaluation->id}/validar")
            ->assertOk()->assertJsonPath('status', 'validada');

        $history = $this->actingAs($user)->getJson("/api/avaliacoes/{$evaluation->id}/historico")->assertOk()->json();
        $this->assertCount(3, $history);
    }

    public function test_rejeitar_exige_motivo_e_devolve_a_rejeitada(): void
    {
        $evaluation = Evaluation::factory()->create(['status' => 'submetida']);
        $user = $this->comPapel(AppRole::Admin);

        $this->actingAs($user)->postJson("/api/avaliacoes/{$evaluation->id}/rejeitar", [])
            ->assertUnprocessable();

        $rejected = $this->actingAs($user)->postJson("/api/avaliacoes/{$evaluation->id}/rejeitar", [
            'reason' => 'Faltam evidências de desempenho.',
        ])->assertOk()->json();

        $this->assertSame('rejeitada', $rejected['status']);
        $this->assertSame('Faltam evidências de desempenho.', $rejected['rejectionReason']);
    }

    public function test_reabrir_limpa_carimbos_e_devolve_a_rascunho(): void
    {
        $evaluation = Evaluation::factory()->create([
            'status' => 'validada',
            'submitted_at' => now(),
            'approved_at' => now(),
            'acknowledged_at' => now(),
        ]);
        $user = $this->comPapel(AppRole::Admin);

        $reopened = $this->actingAs($user)->postJson("/api/avaliacoes/{$evaluation->id}/reabrir")
            ->assertOk()->json();

        $this->assertSame('rascunho', $reopened['status']);
        $this->assertNull($reopened['submittedAt']);
        $this->assertNull($reopened['approvedAt']);
        $this->assertNull($reopened['acknowledgedAt']);
    }

    public function test_transicao_invalida_devolve_409(): void
    {
        $evaluation = Evaluation::factory()->create(['status' => 'rascunho']);
        $user = $this->comPapel(AppRole::Admin);

        $this->actingAs($user)->postJson("/api/avaliacoes/{$evaluation->id}/aprovar")
            ->assertStatus(409);
    }

    public function test_destroy_avaliacao_arrasta_pontuacoes_e_historico_em_cascata(): void
    {
        $evaluation = Evaluation::factory()->create();
        $score = EvaluationScore::factory()->create(['evaluation_id' => $evaluation->id]);
        $history = EvaluationHistory::factory()->create(['evaluation_id' => $evaluation->id]);
        $user = $this->comPapel(AppRole::Admin);

        $this->actingAs($user)->deleteJson("/api/avaliacoes/{$evaluation->id}")->assertNoContent();

        $this->assertDatabaseMissing('evaluations', ['id' => $evaluation->id]);
        $this->assertDatabaseMissing('evaluation_scores', ['id' => $score->id]);
        $this->assertDatabaseMissing('evaluation_history', ['id' => $history->id]);
    }

    public function test_destroy_ciclo_arrasta_criterios_e_avaliacoes_em_cascata(): void
    {
        $cycle = EvaluationCycle::factory()->create();
        $criteria = EvaluationCriteria::factory()->create(['cycle_id' => $cycle->id]);
        $evaluation = Evaluation::factory()->create(['cycle_id' => $cycle->id]);
        $user = $this->comPapel(AppRole::Admin);

        $this->actingAs($user)->deleteJson("/api/avaliacoes/ciclos/{$cycle->id}")->assertNoContent();

        $this->assertDatabaseMissing('evaluation_cycles', ['id' => $cycle->id]);
        $this->assertDatabaseMissing('evaluation_criteria', ['id' => $criteria->id]);
        $this->assertDatabaseMissing('evaluations', ['id' => $evaluation->id]);
    }

    public function test_stats_contabiliza_ciclo_recem_criado_ainda_planeado(): void
    {
        // Regressao: um ciclo criado com o estado por omissao ('planeado')
        // nunca era contabilizado em nenhum KPI, apesar de ja aparecer na
        // listagem "Ciclos" (ver auditoria funcional — Avaliacao/Indicador).
        EvaluationCycle::factory()->create(['status' => 'planeado']);
        EvaluationCycle::factory()->create(['status' => 'aberto']);
        $user = $this->comPapel(AppRole::Admin);

        $response = $this->actingAs($user)->getJson('/api/avaliacoes/stats');

        $response->assertOk()->assertJsonPath('totalCycles', 2);
    }

    public function test_store_sem_permissao_devolve_403(): void
    {
        $user = $this->comPapel(AppRole::Isv);

        $this->actingAs($user)->postJson('/api/avaliacoes/ciclos', [
            'name' => 'X', 'year' => 2026, 'startDate' => '2026-01-01', 'endDate' => '2026-12-31',
        ])->assertForbidden();
    }
}

<?php

namespace Tests\Feature;

use App\Enums\AppRole;
use App\Models\RhLaboratorioColaborador;
use App\Models\RhLaboratorioContrato;
use App\Models\RhTransversalAusencia;
use App\Models\RhTransversalColaborador;
use App\Models\RhTransversalContrato;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Dualidade obrigatória (ver SIG-IIV-MEMORIA-PROJETO.md secção 6): RH
 * transversal e RH laboratorial têm de ser esquemas genuinamente separados.
 */
class RhTest extends TestCase
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

    public function test_gate_dualidade_tabelas_estao_genuinamente_separadas(): void
    {
        foreach (['rh_transversal_colaboradores', 'rh_transversal_contratos', 'rh_transversal_ausencias',
            'rh_laboratorio_colaboradores', 'rh_laboratorio_contratos', 'rh_laboratorio_ausencias'] as $table) {
            $this->assertTrue(Schema::hasTable($table));
        }

        $transversal = RhTransversalColaborador::factory()->create(['employee_number' => 'DUP-001']);
        $laboratorio = RhLaboratorioColaborador::factory()->create(['employee_number' => 'DUP-001']);

        $this->assertDatabaseHas('rh_transversal_colaboradores', ['id' => $transversal->id, 'employee_number' => 'DUP-001']);
        $this->assertDatabaseHas('rh_laboratorio_colaboradores', ['id' => $laboratorio->id, 'employee_number' => 'DUP-001']);
        $this->assertDatabaseMissing('rh_laboratorio_colaboradores', ['id' => $transversal->id]);
        $this->assertDatabaseMissing('rh_transversal_colaboradores', ['id' => $laboratorio->id]);
    }

    public function test_store_colaborador_contrato_e_ausencia_transversal(): void
    {
        $user = $this->comPapel(AppRole::GestorRh);

        $colaborador = $this->actingAs($user)->postJson('/api/rh-transversal/colaboradores', [
            'employeeNumber' => 'IIV-0100', 'fullName' => 'Ana Silva',
        ])->assertCreated()->json();

        $this->assertTrue($colaborador['isActive']);

        $contrato = $this->actingAs($user)->postJson('/api/rh-transversal/contratos', [
            'employeeId' => $colaborador['id'], 'contractType' => 'efectivo', 'position' => 'Técnica',
            'startDate' => '2026-01-01', 'salary' => 250000,
        ])->assertCreated()->json();

        $this->assertSame('AOA', $contrato['currency']);

        $ausencia = $this->actingAs($user)->postJson('/api/rh-transversal/ausencias', [
            'employeeId' => $colaborador['id'], 'leaveType' => 'ferias',
            'startDate' => '2026-03-01', 'endDate' => '2026-03-10', 'reason' => 'Férias anuais',
        ])->assertCreated()->json();

        $this->assertSame(10, $ausencia['days']);
    }

    public function test_store_colaborador_aceita_cargo_transversal_e_laboratorio(): void
    {
        $user = $this->comPapel(AppRole::GestorRh);

        $transversal = $this->actingAs($user)->postJson('/api/rh-transversal/colaboradores', [
            'employeeNumber' => 'IIV-0200', 'fullName' => 'Beatriz Costa', 'position' => 'Técnica Administrativa',
        ])->assertCreated()->json();

        $this->assertSame('Técnica Administrativa', $transversal['position']);

        $laboratorio = $this->actingAs($user)->postJson('/api/rh-laboratorio/colaboradores', [
            'employeeNumber' => 'IIV-0201', 'fullName' => 'Carlos Neto', 'position' => 'Técnico de Laboratório',
        ])->assertCreated()->json();

        $this->assertSame('Técnico de Laboratório', $laboratorio['position']);
    }

    public function test_store_ausencia_sem_motivo_falha_validacao_transversal_e_laboratorio(): void
    {
        $user = $this->comPapel(AppRole::GestorRh);
        $transversal = RhTransversalColaborador::factory()->create();
        $laboratorio = RhLaboratorioColaborador::factory()->create();

        $this->actingAs($user)->postJson('/api/rh-transversal/ausencias', [
            'employeeId' => $transversal->id, 'leaveType' => 'ferias',
            'startDate' => '2026-03-01', 'endDate' => '2026-03-10',
        ])->assertStatus(422)->assertJsonValidationErrors('reason');

        $this->actingAs($user)->postJson('/api/rh-laboratorio/ausencias', [
            'employeeId' => $laboratorio->id, 'leaveType' => 'ferias',
            'startDate' => '2026-03-01', 'endDate' => '2026-03-10',
        ])->assertStatus(422)->assertJsonValidationErrors('reason');
    }

    public function test_destroy_colaborador_transversal_arrasta_contratos_e_ausencias_em_cascata_sem_afectar_laboratorio(): void
    {
        $transversal = RhTransversalColaborador::factory()->create();
        $contrato = RhTransversalContrato::factory()->create(['employee_id' => $transversal->id]);
        $ausencia = RhTransversalAusencia::factory()->create(['employee_id' => $transversal->id]);
        $laboratorio = RhLaboratorioColaborador::factory()->create();
        $contratoLab = RhLaboratorioContrato::factory()->create(['employee_id' => $laboratorio->id]);
        $user = $this->comPapel(AppRole::GestorRh);

        $this->actingAs($user)->deleteJson("/api/rh-transversal/colaboradores/{$transversal->id}")->assertNoContent();

        $this->assertDatabaseMissing('rh_transversal_colaboradores', ['id' => $transversal->id]);
        $this->assertDatabaseMissing('rh_transversal_contratos', ['id' => $contrato->id]);
        $this->assertDatabaseMissing('rh_transversal_ausencias', ['id' => $ausencia->id]);
        $this->assertDatabaseHas('rh_laboratorio_colaboradores', ['id' => $laboratorio->id]);
        $this->assertDatabaseHas('rh_laboratorio_contratos', ['id' => $contratoLab->id]);
    }

    public function test_index_filtra_por_departamento_e_activo(): void
    {
        RhTransversalColaborador::factory()->create(['is_active' => true]);
        RhTransversalColaborador::factory()->create(['is_active' => false]);
        $user = $this->comPapel(AppRole::GestorRh);

        $this->actingAs($user)->getJson('/api/rh-transversal/colaboradores?is_active=true')
            ->assertOk()->assertJsonPath('meta.total', 1);
    }

    public function test_store_sem_permissao_devolve_403(): void
    {
        $user = $this->comPapel(AppRole::Isv);

        $this->actingAs($user)->postJson('/api/rh-transversal/colaboradores', [
            'employeeNumber' => 'X', 'fullName' => 'X',
        ])->assertForbidden();
    }
}

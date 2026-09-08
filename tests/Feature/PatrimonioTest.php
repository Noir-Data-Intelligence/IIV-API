<?php

namespace Tests\Feature;

use App\Enums\AppRole;
use App\Models\Department;
use App\Models\Estacao;
use App\Models\PatrimonioCentral;
use App\Models\PatrimonioCentralManutencao;
use App\Models\PatrimonioEstacao;
use App\Models\PatrimonioEstacaoManutencao;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Dualidade obrigatória (ver SIG-IIV-MEMORIA-PROJETO.md secção 6):
 * `patrimonio_central` e `patrimonio_estacao` têm de ser esquemas
 * genuinamente separados — nenhuma tabela partilhada por atalho.
 */
class PatrimonioTest extends TestCase
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

    public function test_gate_dualidade_tabelas_e_manutencoes_estao_genuinamente_separadas(): void
    {
        $this->assertTrue(Schema::hasTable('patrimonio_central'));
        $this->assertTrue(Schema::hasTable('patrimonio_estacao'));
        $this->assertTrue(Schema::hasTable('patrimonio_central_manutencoes'));
        $this->assertTrue(Schema::hasTable('patrimonio_estacao_manutencoes'));

        // Mesmo código pode existir em ambos os esquemas — não há constraint
        // partilhada nem tabela única com discriminador.
        $central = PatrimonioCentral::factory()->create(['code' => 'PAT-DUP-001']);
        $estacao = PatrimonioEstacao::factory()->create(['code' => 'PAT-DUP-001']);

        $this->assertDatabaseHas('patrimonio_central', ['id' => $central->id, 'code' => 'PAT-DUP-001']);
        $this->assertDatabaseHas('patrimonio_estacao', ['id' => $estacao->id, 'code' => 'PAT-DUP-001']);
        $this->assertDatabaseMissing('patrimonio_estacao', ['id' => $central->id]);
        $this->assertDatabaseMissing('patrimonio_central', ['id' => $estacao->id]);
    }

    public function test_store_activo_central_e_manutencao(): void
    {
        $user = $this->comPapel(AppRole::GestorPatrimonio);

        $activo = $this->actingAs($user)->postJson('/api/patrimonio-central', [
            'code' => 'PAT-CTR-100', 'name' => 'Autoclave', 'category' => 'equipamento_laboratorio',
        ])->assertCreated()->json();

        $this->assertSame('activo', $activo['status']);

        $manutencao = $this->actingAs($user)->postJson('/api/patrimonio-central/manutencoes', [
            'assetId' => $activo['id'], 'date' => '2026-02-01', 'type' => 'preventiva',
            'description' => 'Verificação anual',
        ])->assertCreated()->json();

        $this->assertSame($activo['id'], $manutencao['assetId']);
    }

    public function test_store_activo_estacao_exige_station_id(): void
    {
        $user = $this->comPapel(AppRole::GestorPatrimonio);

        $this->actingAs($user)->postJson('/api/patrimonio-estacao', [
            'code' => 'PAT-EST-100', 'name' => 'Gerador', 'category' => 'energia',
        ])->assertUnprocessable();

        $estacao = Estacao::factory()->create();

        $this->actingAs($user)->postJson('/api/patrimonio-estacao', [
            'code' => 'PAT-EST-100', 'name' => 'Gerador', 'category' => 'energia',
            'stationId' => $estacao->id, 'description' => 'Gerador de reserva da estação',
        ])->assertCreated();
    }

    public function test_destroy_activo_central_arrasta_manutencoes_em_cascata_e_nao_afecta_estacao(): void
    {
        $central = PatrimonioCentral::factory()->create();
        $manutencaoCentral = PatrimonioCentralManutencao::factory()->create(['patrimonio_central_id' => $central->id]);
        $estacao = PatrimonioEstacao::factory()->create();
        $manutencaoEstacao = PatrimonioEstacaoManutencao::factory()->create(['patrimonio_estacao_id' => $estacao->id]);
        $user = $this->comPapel(AppRole::GestorPatrimonio);

        $this->actingAs($user)->deleteJson("/api/patrimonio-central/{$central->id}")->assertNoContent();

        $this->assertDatabaseMissing('patrimonio_central', ['id' => $central->id]);
        $this->assertDatabaseMissing('patrimonio_central_manutencoes', ['id' => $manutencaoCentral->id]);
        // O activo/manutenção de estação não é afectado pela remoção em central.
        $this->assertDatabaseHas('patrimonio_estacao', ['id' => $estacao->id]);
        $this->assertDatabaseHas('patrimonio_estacao_manutencoes', ['id' => $manutencaoEstacao->id]);
    }

    public function test_index_filtra_por_categoria_e_estado(): void
    {
        PatrimonioCentral::factory()->create(['category' => 'informatica', 'status' => 'activo']);
        PatrimonioCentral::factory()->create(['category' => 'viatura', 'status' => 'avariado']);
        $user = $this->comPapel(AppRole::GestorPatrimonio);

        $this->actingAs($user)->getJson('/api/patrimonio-central?category=informatica')
            ->assertOk()->assertJsonPath('meta.total', 1);
        $this->actingAs($user)->getJson('/api/patrimonio-central?status=avariado')
            ->assertOk()->assertJsonPath('meta.total', 1);
    }

    public function test_store_sem_permissao_devolve_403(): void
    {
        $user = $this->comPapel(AppRole::Isv);

        $this->actingAs($user)->postJson('/api/patrimonio-central', [
            'code' => 'X', 'name' => 'X', 'category' => 'outros',
        ])->assertForbidden();
    }

    /**
     * Auditoria funcional (01/09/2026, secção 21): "Tornar Descrição
     * obrigatória se aplicável" — só em Estação. Central mantém-se opcional.
     */
    public function test_descricao_e_obrigatoria_em_estacao_mas_opcional_em_central(): void
    {
        $user = $this->comPapel(AppRole::GestorPatrimonio);
        $estacao = Estacao::factory()->create();

        $this->actingAs($user)->postJson('/api/patrimonio-central', [
            'code' => 'PAT-CTR-200', 'name' => 'Microscópio', 'category' => 'equipamento_laboratorio',
        ])->assertCreated();

        $this->actingAs($user)->postJson('/api/patrimonio-estacao', [
            'code' => 'PAT-EST-200', 'name' => 'Bomba de água', 'category' => 'energia',
            'stationId' => $estacao->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('description');

        $this->actingAs($user)->postJson('/api/patrimonio-estacao', [
            'code' => 'PAT-EST-201', 'name' => 'Bomba de água', 'category' => 'energia',
            'stationId' => $estacao->id, 'description' => 'Bomba de irrigação do curral',
        ])->assertCreated();
    }

    /**
     * Auditoria funcional (01/09/2026, secção 21): "Associar Responsável ao
     * Departamento" — o Responsável passa a referenciar um Utilizador real
     * (`responsibleUserId`, mesmo padrão de `IaCenter::responsible()`), e o
     * Departamento apresentado é o desse utilizador.
     */
    public function test_responsavel_referencia_um_utilizador_existente_e_departamento_fica_associado(): void
    {
        $gestor = $this->comPapel(AppRole::GestorPatrimonio);
        $departamento = Department::factory()->create(['name' => 'Laboratório Central']);
        $responsavel = User::factory()->create(['full_name' => 'Ana Kiala']);
        $responsavel->departments()->attach($departamento->id);

        $central = $this->actingAs($gestor)->postJson('/api/patrimonio-central', [
            'code' => 'PAT-CTR-300', 'name' => 'Centrífuga', 'category' => 'equipamento_laboratorio',
            'departmentId' => $departamento->id, 'responsibleUserId' => $responsavel->id,
        ])->assertCreated()->json();

        $this->assertSame($responsavel->id, $central['responsibleUserId']);
        $this->assertSame($departamento->id, $central['departmentId']);
        $this->assertDatabaseHas('patrimonio_central', [
            'id' => $central['id'], 'responsible_user_id' => $responsavel->id, 'department_id' => $departamento->id,
        ]);

        $estacaoModelo = Estacao::factory()->create();
        $estacao = $this->actingAs($gestor)->postJson('/api/patrimonio-estacao', [
            'code' => 'PAT-EST-300', 'name' => 'Balança', 'category' => 'equipamento_laboratorio',
            'stationId' => $estacaoModelo->id, 'description' => 'Balança de pesagem de animais',
            'departmentId' => $departamento->id, 'responsibleUserId' => $responsavel->id,
        ])->assertCreated()->json();

        $this->assertSame($responsavel->id, $estacao['responsibleUserId']);
        $this->assertSame($departamento->id, $estacao['departmentId']);
    }

    public function test_responsavel_tem_de_ser_um_utilizador_existente(): void
    {
        $user = $this->comPapel(AppRole::GestorPatrimonio);

        $this->actingAs($user)->postJson('/api/patrimonio-central', [
            'code' => 'PAT-CTR-301', 'name' => 'Autoclave', 'category' => 'equipamento_laboratorio',
            'responsibleUserId' => (string) Str::uuid(),
        ])->assertUnprocessable()->assertJsonValidationErrors('responsibleUserId');
    }

    public function test_pesquisa_activo_central_pelo_nome_do_responsavel(): void
    {
        $user = $this->comPapel(AppRole::GestorPatrimonio);
        $responsavel = User::factory()->create(['full_name' => 'Bento Muteka']);
        PatrimonioCentral::factory()->create(['code' => 'PAT-CTR-302', 'responsible_user_id' => $responsavel->id]);
        PatrimonioCentral::factory()->create(['code' => 'PAT-CTR-303', 'responsible_user_id' => null]);

        $this->actingAs($user)->getJson('/api/patrimonio-central?search=Bento+Muteka')
            ->assertOk()->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.code', 'PAT-CTR-302');
    }
}

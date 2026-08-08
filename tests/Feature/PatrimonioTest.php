<?php

namespace Tests\Feature;

use App\Enums\AppRole;
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

        $this->actingAs($user)->postJson('/api/patrimonio-estacao', [
            'code' => 'PAT-EST-100', 'name' => 'Gerador', 'category' => 'energia',
            'stationId' => (string) Str::uuid(),
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
}

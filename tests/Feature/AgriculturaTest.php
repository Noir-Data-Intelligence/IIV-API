<?php

namespace Tests\Feature;

use App\Enums\AppRole;
use App\Models\Crop;
use App\Models\Estacao;
use App\Models\Field;
use App\Models\Harvest;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AgriculturaTest extends TestCase
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

    public function test_store_campo_e_colheita(): void
    {
        $estacao = Estacao::factory()->create();
        $cultura = Crop::factory()->create();
        $user = $this->comPapel(AppRole::GestorEstacao);

        $campo = $this->actingAs($user)->postJson('/api/campos', [
            'stationId' => $estacao->id, 'cropId' => $cultura->id, 'areaHa' => 3.5,
        ])->assertCreated()->json();

        $this->assertSame('planeado', $campo['status']);

        $colheita = $this->actingAs($user)->postJson('/api/colheitas', [
            'fieldId' => $campo['id'], 'harvestDate' => '2026-06-01', 'quantity' => 1200,
        ])->assertCreated()->json();

        $this->assertSame('kg', $colheita['unit']);
    }

    public function test_apagar_cultura_arrasta_campos_e_colheitas_em_2_niveis(): void
    {
        $cultura = Crop::factory()->create();
        $campo = Field::factory()->create(['crop_id' => $cultura->id]);
        $colheita = Harvest::factory()->create(['field_id' => $campo->id]);
        $user = $this->comPapel(AppRole::GestorEstacao);

        $this->actingAs($user)->deleteJson("/api/culturas/{$cultura->id}")->assertNoContent();

        $this->assertDatabaseMissing('crops', ['id' => $cultura->id]);
        $this->assertDatabaseMissing('fields', ['id' => $campo->id]);
        $this->assertDatabaseMissing('harvests', ['id' => $colheita->id]);
    }

    public function test_apagar_campo_arrasta_as_suas_colheitas(): void
    {
        $campo = Field::factory()->create();
        $colheita = Harvest::factory()->create(['field_id' => $campo->id]);
        $user = $this->comPapel(AppRole::GestorEstacao);

        $this->actingAs($user)->deleteJson("/api/campos/{$campo->id}")->assertNoContent();

        $this->assertDatabaseMissing('fields', ['id' => $campo->id]);
        $this->assertDatabaseMissing('harvests', ['id' => $colheita->id]);
    }

    public function test_index_filtra_por_estacao_e_cultura(): void
    {
        $estacaoA = Estacao::factory()->create();
        $estacaoB = Estacao::factory()->create();
        Field::factory()->create(['station_id' => $estacaoA->id]);
        Field::factory()->create(['station_id' => $estacaoB->id]);
        $user = $this->comPapel(AppRole::GestorEstacao);

        $this->actingAs($user)->getJson("/api/campos?station_id={$estacaoA->id}")
            ->assertOk()->assertJsonPath('meta.total', 1);
    }

    public function test_store_sem_permissao_devolve_403(): void
    {
        $user = $this->comPapel(AppRole::Isv);

        $this->actingAs($user)->postJson('/api/culturas', ['name' => 'X'])->assertForbidden();
    }
}

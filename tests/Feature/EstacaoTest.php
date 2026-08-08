<?php

namespace Tests\Feature;

use App\Enums\AppRole;
use App\Models\Estacao;
use App\Models\PatrimonioEstacao;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class EstacaoTest extends TestCase
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

    public function test_store_e_index_com_filtro_de_pesquisa(): void
    {
        $user = $this->comPapel(AppRole::GestorEstacao);

        $this->actingAs($user)->postJson('/api/estacoes', [
            'name' => 'Estação de Humpata', 'location' => 'Huíla',
        ])->assertCreated()->assertJsonPath('stationType', 'zootecnica');

        Estacao::factory()->create(['name' => 'Estação de Malanje']);

        $this->actingAs($user)->getJson('/api/estacoes?search=Humpata')
            ->assertOk()->assertJsonPath('meta.total', 1);
    }

    public function test_gate_fk_retroactiva_patrimonio_estacao_liga_a_estacoes_reais(): void
    {
        $estacao = Estacao::factory()->create();
        $activo = PatrimonioEstacao::factory()->create(['station_id' => $estacao->id]);

        $this->assertDatabaseHas('patrimonio_estacao', ['id' => $activo->id, 'station_id' => $estacao->id]);

        // restrictOnDelete: não é possível apagar uma estação com património associado.
        $this->expectException(QueryException::class);
        $estacao->delete();
    }

    public function test_store_sem_permissao_devolve_403(): void
    {
        $user = $this->comPapel(AppRole::Isv);

        $this->actingAs($user)->postJson('/api/estacoes', ['name' => 'X'])->assertForbidden();
    }
}

<?php

namespace Tests\Feature;

use App\Enums\AppRole;
use App\Models\CriterioRejeicao;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CriterioRejeicaoTest extends TestCase
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

    public function test_index_lista_criterios_gerais_e_especificos_do_laboratorio(): void
    {
        CriterioRejeicao::factory()->count(2)->create(['laboratorio_id' => null]);

        $response = $this->actingAs($this->comPapel(AppRole::Tecnico))->getJson('/api/criterios-rejeicao');

        $response->assertOk();
        $this->assertCount(2, $response->json());
    }

    public function test_store_sem_permissao_de_escrita_devolve_403(): void
    {
        $this->actingAs($this->comPapel(AppRole::Tecnico))
            ->postJson('/api/criterios-rejeicao', ['codigo' => 'REJ-999', 'motivo' => 'X'])
            ->assertForbidden();
    }

    public function test_store_como_admin_cria_criterio(): void
    {
        $response = $this->actingAs($this->comPapel(AppRole::Admin))
            ->postJson('/api/criterios-rejeicao', ['codigo' => 'REJ-999', 'motivo' => 'Amostra fora de prazo']);

        $response->assertCreated()->assertJsonPath('codigo', 'REJ-999');
    }
}

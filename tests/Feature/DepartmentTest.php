<?php

namespace Tests\Feature;

use App\Enums\AppRole;
use App\Models\Department;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DepartmentTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(AppRole $role): User
    {
        $user = User::factory()->create();

        UserRole::query()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'role' => $role,
        ]);

        return $user;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_index_devolve_o_envelope_paginado(): void
    {
        Department::factory()->count(3)->create();
        $admin = $this->userWithRole(AppRole::Admin);

        $response = $this->actingAs($admin)->getJson('/api/departamentos');

        $response->assertOk()
            ->assertJsonStructure(['data', 'meta' => ['currentPage', 'perPage', 'total', 'lastPage']])
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.total', 3);
    }

    public function test_index_respeita_o_parametro_per_page_snake_case(): void
    {
        // O frontend envia sempre `page`/`per_page` (snake_case) mesmo com o
        // resto do contrato em camelCase — ver services/api/departamentos.ts
        // `toQuery()`. Regressão: perPage (camelCase) já foi lido por engano.
        Department::factory()->count(5)->create();
        $admin = $this->userWithRole(AppRole::Admin);

        $response = $this->actingAs($admin)->getJson('/api/departamentos?per_page=2&page=2');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.perPage', 2)
            ->assertJsonPath('meta.currentPage', 2)
            ->assertJsonPath('meta.lastPage', 3);
    }

    public function test_store_cria_departamento_como_admin(): void
    {
        $admin = $this->userWithRole(AppRole::Admin);

        $response = $this->actingAs($admin)->postJson('/api/departamentos', [
            'name' => 'Departamento Novo',
            'description' => 'Descrição de teste.',
        ]);

        $response->assertCreated()->assertJsonPath('data.name', 'Departamento Novo');
        $this->assertDatabaseHas('departments', ['name' => 'Departamento Novo']);
    }

    public function test_store_sem_permissao_de_escrita_devolve_403(): void
    {
        // "tecnico" nao tem "departamentos" nem em view nem em write na
        // matriz seedada (ver RolePermissionSeeder) — bom caso negativo.
        $tecnico = $this->userWithRole(AppRole::Tecnico);

        $this->actingAs($tecnico)
            ->postJson('/api/departamentos', ['name' => 'X'])
            ->assertForbidden();
    }

    public function test_update_altera_o_departamento_como_admin(): void
    {
        $admin = $this->userWithRole(AppRole::Admin);
        $department = Department::factory()->create(['name' => 'Nome Antigo']);

        $response = $this->actingAs($admin)->putJson("/api/departamentos/{$department->id}", [
            'name' => 'Nome Novo',
        ]);

        $response->assertOk()->assertJsonPath('data.name', 'Nome Novo');
    }

    public function test_destroy_remove_o_departamento_como_admin(): void
    {
        $admin = $this->userWithRole(AppRole::Admin);
        $department = Department::factory()->create();

        $this->actingAs($admin)
            ->deleteJson("/api/departamentos/{$department->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('departments', ['id' => $department->id]);
    }

    public function test_visitante_nao_autenticado_recebe_401(): void
    {
        $this->getJson('/api/departamentos')->assertUnauthorized();
    }
}

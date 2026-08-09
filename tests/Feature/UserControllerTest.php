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

/**
 * Módulo Utilizadores (`GET/PUT/DELETE /users`) — distinto de `/rbac/users`
 * (gestão de papéis apenas). Regressão real: o frontend chamava `/users`
 * (Dashboard/BI/PainelDetalhe/Missoes/...) e a rota nunca existiu, gerando
 * 404 silencioso (react-query engole o erro, KPIs caem para 0).
 */
class UserControllerTest extends TestCase
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

    public function test_index_devolve_array_simples_sem_envelope_paginado(): void
    {
        User::factory()->count(3)->create();
        $admin = $this->userWithRole(AppRole::Admin);

        $response = $this->actingAs($admin)->getJson('/api/users');

        $response->assertOk();
        $this->assertIsArray($response->json());
        $this->assertArrayNotHasKey('data', $response->json());
    }

    public function test_index_filtra_por_role(): void
    {
        $admin = $this->userWithRole(AppRole::Admin);
        $this->userWithRole(AppRole::Tecnico);

        $response = $this->actingAs($admin)->getJson('/api/users?role=tecnico');

        $roles = collect($response->json())->pluck('roles')->flatten();
        $this->assertTrue($roles->contains('tecnico'));
        $this->assertFalse($roles->contains('admin'));
    }

    public function test_index_sem_permissao_devolve_403(): void
    {
        // "tecnico" nao tem "utilizadores" na matriz seedada.
        $tecnico = $this->userWithRole(AppRole::Tecnico);

        $this->actingAs($tecnico)->getJson('/api/users')->assertForbidden();
    }

    public function test_update_altera_nome_papeis_e_departamentos(): void
    {
        $admin = $this->userWithRole(AppRole::Admin);
        $user = User::factory()->create(['full_name' => 'Nome Antigo']);
        $department = Department::factory()->create();

        $response = $this->actingAs($admin)->putJson("/api/users/{$user->id}", [
            'fullName' => 'Nome Novo',
            'roles' => ['tecnico'],
            'departmentIds' => [$department->id],
        ]);

        $response->assertOk()
            ->assertJsonPath('fullName', 'Nome Novo')
            ->assertJsonPath('roles', ['tecnico'])
            ->assertJsonPath('departmentIds', [$department->id]);
    }

    public function test_destroy_remove_o_utilizador_como_admin(): void
    {
        $admin = $this->userWithRole(AppRole::Admin);
        $user = User::factory()->create();

        $this->actingAs($admin)->deleteJson("/api/users/{$user->id}")->assertNoContent();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_visitante_nao_autenticado_recebe_401(): void
    {
        $this->getJson('/api/users')->assertUnauthorized();
    }
}

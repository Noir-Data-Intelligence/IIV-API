<?php

namespace Tests\Feature;

use App\Enums\AppRole;
use App\Enums\ModuleKey;
use App\Models\User;
use App\Models\UserRole;
use App\Services\PermissionMatrixService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Paridade entre `role_permissions` seedado e a matriz de referencia (ver
 * PLANO-BACKEND-LARAVEL.txt seccao 12 — "teste de paridade automatizado").
 */
class RbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function userWithRole(AppRole $role): User
    {
        $user = User::factory()->create();
        UserRole::query()->create(['id' => (string) Str::uuid(), 'user_id' => $user->id, 'role' => $role]);

        return $user;
    }

    public function test_admin_tem_acesso_total_sem_depender_da_tabela(): void
    {
        $service = app(PermissionMatrixService::class);
        $matrix = $service->forRole(AppRole::Admin);

        $this->assertSame(ModuleKey::ALL, $matrix['view']);
        $this->assertSame(ModuleKey::ALL, $matrix['write']);
    }

    public function test_isv_tem_apenas_acesso_de_leitura_minimo(): void
    {
        $service = app(PermissionMatrixService::class);
        $matrix = $service->forRole(AppRole::Isv);

        $this->assertEqualsCanonicalizing(['painel', 'processos', 'historico-alertas'], $matrix['view']);
        $this->assertSame([], $matrix['write']);
    }

    public function test_diretor_ve_tudo_mas_nao_escreve_nada(): void
    {
        $service = app(PermissionMatrixService::class);
        $matrix = $service->forRole(AppRole::Diretor);

        $this->assertSame(ModuleKey::ALL, $matrix['view']);
        $this->assertSame([], $matrix['write']);
    }

    public function test_get_permissions_devolve_a_matriz_completa(): void
    {
        $admin = $this->userWithRole(AppRole::Admin);

        $response = $this->actingAs($admin)->getJson('/api/rbac/permissions');

        // 11 papeis nao-admin x 38 modulos.
        $response->assertOk()->assertJsonCount(11 * count(ModuleKey::ALL));
    }

    public function test_put_permissions_substitui_a_matriz_e_invalida_a_cache(): void
    {
        $admin = $this->userWithRole(AppRole::Admin);

        $this->actingAs($admin)->putJson('/api/rbac/permissions', [
            ['role' => 'tecnico', 'module' => 'financeiro', 'canView' => true, 'canWrite' => false],
        ])->assertNoContent();

        $service = app(PermissionMatrixService::class);
        $this->assertTrue($service->canView(AppRole::Tecnico, 'financeiro'));
        // A substituicao e total: um modulo que o tecnico via antes (ex: analises)
        // deixa de estar la, porque so foi enviada uma entrada no PUT.
        $this->assertFalse($service->canView(AppRole::Tecnico, 'analises'));
    }

    public function test_atribuir_e_remover_papel_de_um_utilizador(): void
    {
        $admin = $this->userWithRole(AppRole::Admin);
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->postJson("/api/rbac/users/{$target->id}/roles", ['role' => 'gestor-stock'])
            ->assertOk()
            ->assertJsonPath('roles', ['gestor-stock']);

        $this->actingAs($admin)
            ->deleteJson("/api/rbac/users/{$target->id}/roles/gestor-stock")
            ->assertNoContent();

        $this->assertSame([], $target->fresh()->roleValues());
    }

    public function test_utilizador_sem_papel_rbac_recebe_403(): void
    {
        $tecnico = $this->userWithRole(AppRole::Tecnico);

        $this->actingAs($tecnico)->getJson('/api/rbac/users')->assertForbidden();
    }
}

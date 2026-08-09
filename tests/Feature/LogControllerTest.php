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
 * Módulo Logs de Actividade (`GET /logs`, só leitura) — sobre o trilho de
 * auditoria transversal `App\Concerns\LogsAuditTrail`. Regressão real: a
 * rota nunca existiu apesar do modelo `AuditLog` já estar a gravar dados
 * desde a Onda 1.
 */
class LogControllerTest extends TestCase
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

    public function test_index_devolve_o_envelope_paginado_com_entidade_e_autor_resolvidos(): void
    {
        $admin = $this->userWithRole(AppRole::Admin);
        $this->actingAs($admin)->postJson('/api/departamentos', ['name' => 'Departamento Auditado']);

        $response = $this->actingAs($admin)->getJson('/api/logs');

        $response->assertOk()
            ->assertJsonStructure(['data', 'meta' => ['currentPage', 'perPage', 'total', 'lastPage']]);

        $entry = collect($response->json('data'))->firstWhere('entityType', 'Department');
        $this->assertNotNull($entry, 'devia existir um log da criação do departamento');
        $this->assertSame('created', $entry['action']);
        $this->assertSame($admin->full_name, $entry['userName']);
    }

    public function test_index_filtra_por_action(): void
    {
        $admin = $this->userWithRole(AppRole::Admin);
        $department = Department::factory()->create();
        $this->actingAs($admin)->putJson("/api/departamentos/{$department->id}", ['name' => 'Renomeado']);

        $response = $this->actingAs($admin)->getJson('/api/logs?action=updated');

        $this->assertTrue(collect($response->json('data'))->every(fn ($log) => $log['action'] === 'updated'));
    }

    public function test_index_sem_permissao_devolve_403(): void
    {
        // "tecnico" nao tem "logs" na matriz seedada (só director-laboratorio
        // e responsavel-qualidade têm).
        $tecnico = $this->userWithRole(AppRole::Tecnico);

        $this->actingAs($tecnico)->getJson('/api/logs')->assertForbidden();
    }

    public function test_visitante_nao_autenticado_recebe_401(): void
    {
        $this->getJson('/api/logs')->assertUnauthorized();
    }
}

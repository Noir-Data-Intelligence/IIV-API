<?php

namespace Tests\Feature;

use App\Enums\AppRole;
use App\Models\Auditoria;
use App\Models\Department;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuditoriaTest extends TestCase
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

    public function test_index_requer_autenticacao(): void
    {
        $this->getJson('/api/auditorias')->assertUnauthorized();
    }

    public function test_store_sem_permissao_de_escrita_devolve_403(): void
    {
        $user = $this->comPapel(AppRole::Isv);

        $this->actingAs($user)->postJson('/api/auditorias', [
            'title' => 'Auditoria X', 'auditType' => 'interna', 'auditor' => 'Fulano', 'scheduledDate' => '2026-09-01',
        ])->assertForbidden();
    }

    public function test_store_resolve_department_name(): void
    {
        $department = Department::factory()->create(['name' => 'Laboratório Central']);
        $user = $this->comPapel(AppRole::ResponsavelQualidade);

        $response = $this->actingAs($user)->postJson('/api/auditorias', [
            'title' => 'Auditoria ISO 9001',
            'auditType' => 'ISO',
            'auditor' => 'Maria Externa',
            'scheduledDate' => '2026-09-15',
            'departmentId' => $department->id,
        ]);

        $response->assertCreated()->assertJsonPath('departmentName', 'Laboratório Central');
    }

    public function test_audit_type_invalido_devolve_422(): void
    {
        $user = $this->comPapel(AppRole::ResponsavelQualidade);

        $this->actingAs($user)->postJson('/api/auditorias', [
            'title' => 'Auditoria X', 'auditType' => 'invalido', 'auditor' => 'Fulano', 'scheduledDate' => '2026-09-01',
        ])->assertStatus(422);
    }

    public function test_show_e_destroy(): void
    {
        $auditoria = Auditoria::factory()->create();
        $user = $this->comPapel(AppRole::DirectorLaboratorio);

        $this->actingAs($user)->getJson("/api/auditorias/{$auditoria->id}")->assertOk();
        $this->actingAs($user)->deleteJson("/api/auditorias/{$auditoria->id}")->assertNoContent();
        $this->assertDatabaseMissing('auditorias', ['id' => $auditoria->id]);
    }
}

<?php

namespace Tests\Feature;

use App\Enums\AppRole;
use App\Models\Department;
use App\Models\Documento;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DocumentPermissionTest extends TestCase
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

    public function test_store_resolve_nome_do_departamento(): void
    {
        $documento = Documento::factory()->create();
        $department = Department::factory()->create(['name' => 'Direcção Técnica']);
        $user = $this->comPapel(AppRole::DirectorLaboratorio);

        $response = $this->actingAs($user)->postJson('/api/document-permissions', [
            'documentId' => $documento->id,
            'departmentId' => $department->id,
            'canEdit' => true,
        ]);

        $response->assertCreated()->assertJsonPath('department.name', 'Direcção Técnica');
    }

    public function test_index_filtra_por_documento(): void
    {
        $documentoA = Documento::factory()->create();
        $documentoB = Documento::factory()->create();
        $user = $this->comPapel(AppRole::DirectorLaboratorio);

        $this->actingAs($user)->postJson('/api/document-permissions', ['documentId' => $documentoA->id, 'role' => 'tecnico', 'canEdit' => false]);
        $this->actingAs($user)->postJson('/api/document-permissions', ['documentId' => $documentoB->id, 'role' => 'tecnico', 'canEdit' => false]);

        $response = $this->actingAs($user)->getJson("/api/document-permissions?document_id={$documentoA->id}");
        $response->assertOk();
        $this->assertCount(1, $response->json());
    }
}

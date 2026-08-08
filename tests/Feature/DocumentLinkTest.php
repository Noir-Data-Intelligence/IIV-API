<?php

namespace Tests\Feature;

use App\Enums\AppRole;
use App\Models\Documento;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DocumentLinkTest extends TestCase
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

    public function test_store_e_index_com_resumo_do_documento(): void
    {
        $documento = Documento::factory()->create(['title' => 'Certificado ISO']);
        $user = $this->comPapel(AppRole::DirectorLaboratorio);
        $entityId = (string) Str::uuid();

        $created = $this->actingAs($user)->postJson('/api/document-links', [
            'documentId' => $documento->id,
            'entityType' => 'auditoria',
            'entityId' => $entityId,
        ]);
        $created->assertCreated()->assertJsonPath('document.title', 'Certificado ISO');

        $listed = $this->actingAs($user)->getJson("/api/document-links?entity_type=auditoria&entity_id={$entityId}");
        $listed->assertOk();
        $this->assertCount(1, $listed->json());
    }

    public function test_destroy_remove_a_ligacao(): void
    {
        $documento = Documento::factory()->create();
        $user = $this->comPapel(AppRole::DirectorLaboratorio);

        $created = $this->actingAs($user)->postJson('/api/document-links', [
            'documentId' => $documento->id, 'entityType' => 'lote', 'entityId' => (string) Str::uuid(),
        ]);

        $this->actingAs($user)->deleteJson("/api/document-links/{$created->json('id')}")->assertNoContent();
    }
}

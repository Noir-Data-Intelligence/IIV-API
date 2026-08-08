<?php

namespace Tests\Feature;

use App\Enums\AppRole;
use App\Models\DocCategory;
use App\Models\Documento;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DocumentoTest extends TestCase
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
        $this->getJson('/api/documentos')->assertUnauthorized();
    }

    public function test_categories_devolve_catalogo_ordenado(): void
    {
        DocCategory::factory()->create(['name' => 'B', 'sort_order' => 2]);
        DocCategory::factory()->create(['name' => 'A', 'sort_order' => 1]);
        $user = $this->comPapel(AppRole::DirectorLaboratorio);

        $response = $this->actingAs($user)->getJson('/api/document-categories');

        $response->assertOk();
        $this->assertSame('A', $response->json('0.name'));
    }

    public function test_store_usa_o_utilizador_autenticado_como_owner_por_omissao(): void
    {
        $user = $this->comPapel(AppRole::DirectorLaboratorio);

        $response = $this->actingAs($user)->postJson('/api/documentos', ['title' => 'Manual de Qualidade']);

        $response->assertCreated()
            ->assertJsonPath('ownerId', $user->id)
            ->assertJsonPath('status', 'rascunho')
            ->assertJsonPath('visibility', 'publico');
    }

    public function test_store_sem_permissao_de_escrita_devolve_403(): void
    {
        $user = $this->comPapel(AppRole::Isv);

        $this->actingAs($user)->postJson('/api/documentos', ['title' => 'X'])->assertForbidden();
    }

    public function test_nova_versao_torna_se_corrente_e_devolve_o_documento_a_rascunho(): void
    {
        $documento = Documento::factory()->create(['status' => 'aprovado']);
        $user = $this->comPapel(AppRole::DirectorLaboratorio);

        $response = $this->actingAs($user)->postJson("/api/documentos/{$documento->id}/versions", [
            'fileName' => 'manual-v2.pdf',
        ]);

        $response->assertCreated()->assertJsonPath('versionNumber', 1);

        $documento->refresh();
        $this->assertSame('rascunho', $documento->status);
        $this->assertSame($response->json('id'), $documento->current_version_id);
    }

    public function test_segunda_versao_incrementa_o_numero(): void
    {
        $documento = Documento::factory()->create();
        $user = $this->comPapel(AppRole::DirectorLaboratorio);

        $this->actingAs($user)->postJson("/api/documentos/{$documento->id}/versions", [])->assertCreated();
        $second = $this->actingAs($user)->postJson("/api/documentos/{$documento->id}/versions", []);

        $second->assertCreated()->assertJsonPath('versionNumber', 2);
    }

    public function test_destroy_remove_o_documento(): void
    {
        $documento = Documento::factory()->create();
        $user = $this->comPapel(AppRole::DirectorLaboratorio);

        $this->actingAs($user)->deleteJson("/api/documentos/{$documento->id}")->assertNoContent();
        $this->assertDatabaseMissing('documentos', ['id' => $documento->id]);
    }
}

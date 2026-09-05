<?php

namespace Tests\Feature;

use App\Enums\AppRole;
use App\Models\DocCategory;
use App\Models\Department;
use App\Models\Documento;
use App\Models\DocumentPermission;
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

    public function test_documento_privado_nao_aparece_na_listagem_de_quem_nao_tem_acesso(): void
    {
        Documento::factory()->create(['title' => 'Privado Sem Acesso', 'visibility' => 'privado']);
        $user = $this->comPapel(AppRole::DirectorLaboratorio);

        $response = $this->actingAs($user)->getJson('/api/documentos');

        $titles = collect($response->json('data'))->pluck('title');
        $this->assertFalse($titles->contains('Privado Sem Acesso'));
    }

    public function test_documento_privado_devolve_404_a_quem_nao_tem_acesso(): void
    {
        // 404, nao 403: nao deve sequer confirmar que o documento existe.
        $documento = Documento::factory()->create(['visibility' => 'privado']);
        $user = $this->comPapel(AppRole::DirectorLaboratorio);

        $this->actingAs($user)->getJson("/api/documentos/{$documento->id}")->assertNotFound();
    }

    public function test_dono_ve_sempre_o_seu_documento_privado(): void
    {
        $user = $this->comPapel(AppRole::DirectorLaboratorio);
        $documento = Documento::factory()->create(['visibility' => 'privado', 'owner_id' => $user->id]);

        $this->actingAs($user)->getJson("/api/documentos/{$documento->id}")->assertOk();
    }

    public function test_admin_ve_documentos_privados_de_outros(): void
    {
        $documento = Documento::factory()->create(['visibility' => 'privado']);
        $admin = $this->comPapel(AppRole::Admin);

        $this->actingAs($admin)->getJson("/api/documentos/{$documento->id}")->assertOk();
    }

    public function test_concessao_por_papel_da_acesso_a_documento_privado(): void
    {
        $documento = Documento::factory()->create(['visibility' => 'privado']);
        DocumentPermission::query()->create([
            'id' => (string) Str::uuid(),
            'document_id' => $documento->id,
            'role' => AppRole::DirectorLaboratorio->value,
            'can_edit' => false,
        ]);
        $user = $this->comPapel(AppRole::DirectorLaboratorio);

        $this->actingAs($user)->getJson("/api/documentos/{$documento->id}")->assertOk();
    }

    public function test_update_apenas_com_status_nao_exige_title(): void
    {
        // Regressao: `title` era `required` tambem no update, pelo que um PUT
        // so com `{status}` (mudanca de estado) falhava sempre com 422.
        $documento = Documento::factory()->create(['status' => 'rascunho']);
        $user = $this->comPapel(AppRole::DirectorLaboratorio);

        $response = $this->actingAs($user)
            ->putJson("/api/documentos/{$documento->id}", ['status' => 'submetido']);

        $response->assertOk()->assertJsonPath('status', 'submetido');
    }

    public function test_categories_store_cria_categoria(): void
    {
        $user = $this->comPapel(AppRole::DirectorLaboratorio);

        $response = $this->actingAs($user)->postJson('/api/document-categories', ['name' => 'Qualidade']);

        $response->assertCreated()->assertJsonPath('name', 'Qualidade');
        $this->assertDatabaseHas('doc_categories', ['name' => 'Qualidade']);
    }

    public function test_categories_destroy_bloqueia_se_associada_a_documento(): void
    {
        $categoria = DocCategory::factory()->create();
        Documento::factory()->create(['category_id' => $categoria->id]);
        $user = $this->comPapel(AppRole::DirectorLaboratorio);

        $this->actingAs($user)->deleteJson("/api/document-categories/{$categoria->id}")
            ->assertStatus(422);
        $this->assertDatabaseHas('doc_categories', ['id' => $categoria->id]);
    }

    public function test_concessao_por_departamento_da_acesso_a_documento_de_departamento(): void
    {
        $department = Department::factory()->create();
        $documento = Documento::factory()->create(['visibility' => 'departamento']);
        DocumentPermission::query()->create([
            'id' => (string) Str::uuid(),
            'document_id' => $documento->id,
            'department_id' => $department->id,
            'can_edit' => false,
        ]);
        $user = $this->comPapel(AppRole::DirectorLaboratorio);
        $user->departments()->attach($department->id);

        $this->actingAs($user)->getJson("/api/documentos/{$documento->id}")->assertOk();

        $outroDepartamento = Department::factory()->create();
        $outroUser = $this->comPapel(AppRole::DirectorLaboratorio);
        $outroUser->departments()->attach($outroDepartamento->id);

        $this->actingAs($outroUser)->getJson("/api/documentos/{$documento->id}")->assertNotFound();
    }
}

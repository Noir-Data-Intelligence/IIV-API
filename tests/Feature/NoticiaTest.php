<?php

namespace Tests\Feature;

use App\Enums\AppRole;
use App\Models\Noticia;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NoticiaTest extends TestCase
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

    public function test_index_e_publico_e_devolve_o_envelope_paginado_em_snake_case(): void
    {
        Noticia::factory()->count(3)->create();

        $response = $this->getJson('/api/noticias');

        $response->assertOk()
            ->assertJsonStructure(['data', 'meta' => ['currentPage', 'perPage', 'total', 'lastPage']])
            ->assertJsonStructure(['data' => [['id', 'slug', 'titulo', 'image_path', 'published_at', 'created_at']]]);
    }

    public function test_visitante_anonimo_nunca_ve_rascunhos_nao_publicados(): void
    {
        // Regressão (auditoria de segurança, 2026-08-09): o filtro
        // `published` era só honrado se o cliente o enviasse, nunca
        // imposto — um visitante anónimo podia omiti-lo e ler rascunhos
        // (`GET /noticias?per_page=2000`).
        Noticia::factory()->count(2)->create(['published' => true]);
        Noticia::factory()->unpublished()->count(3)->create();

        $this->getJson('/api/noticias')->assertJsonPath('meta.total', 2);
        $this->getJson('/api/noticias?published=false')->assertJsonPath('meta.total', 2);
    }

    public function test_utilizador_com_modulo_noticias_ve_rascunhos_nao_publicados(): void
    {
        Noticia::factory()->count(2)->create(['published' => true]);
        Noticia::factory()->unpublished()->count(3)->create();
        $admin = $this->userWithRole(AppRole::Admin);

        $this->actingAs($admin)->getJson('/api/noticias')->assertJsonPath('meta.total', 5);
        $this->actingAs($admin)->getJson('/api/noticias?published=false')->assertJsonPath('meta.total', 3);
    }

    public function test_utilizador_sem_modulo_noticias_nao_ve_rascunhos(): void
    {
        Noticia::factory()->unpublished()->count(3)->create();
        // "tecnico" nao tem "noticias" na matriz seedada.
        $tecnico = $this->userWithRole(AppRole::Tecnico);

        $this->actingAs($tecnico)->getJson('/api/noticias')->assertJsonPath('meta.total', 0);
    }

    public function test_show_encontra_por_id_ou_por_slug(): void
    {
        $noticia = Noticia::factory()->create(['slug' => 'vacinacao-em-huambo']);

        $this->getJson("/api/noticias/{$noticia->id}")->assertOk()->assertJsonPath('slug', 'vacinacao-em-huambo');
        $this->getJson('/api/noticias/vacinacao-em-huambo')->assertOk()->assertJsonPath('id', $noticia->id);
    }

    public function test_store_sem_autenticacao_devolve_401(): void
    {
        $this->postJson('/api/noticias', ['titulo' => 'Título'])->assertUnauthorized();
    }

    public function test_store_sem_permissao_de_escrita_devolve_403(): void
    {
        $tecnico = $this->userWithRole(AppRole::Tecnico);

        $this->actingAs($tecnico)->postJson('/api/noticias', ['titulo' => 'Título'])->assertForbidden();
    }

    public function test_store_como_admin_cria_e_gera_slug_automatico(): void
    {
        $admin = $this->userWithRole(AppRole::Admin);

        $response = $this->actingAs($admin)->postJson('/api/noticias', [
            'titulo' => 'Nova Campanha de Vacinação',
            'categoria' => 'Vacinação',
            'published' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('titulo', 'Nova Campanha de Vacinação')
            ->assertJsonPath('published', true);
        $this->assertNotNull($response->json('slug'));
        $this->assertNotNull($response->json('published_at'));
    }

    public function test_destroy_como_admin_remove_a_noticia(): void
    {
        $admin = $this->userWithRole(AppRole::Admin);
        $noticia = Noticia::factory()->create();

        $this->actingAs($admin)->deleteJson("/api/noticias/{$noticia->id}")->assertNoContent();
        $this->assertDatabaseMissing('noticias', ['id' => $noticia->id]);
    }
}

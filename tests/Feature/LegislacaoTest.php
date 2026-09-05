<?php

namespace Tests\Feature;

use App\Enums\AppRole;
use App\Models\Legislacao;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LegislacaoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        UserRole::query()->create(['id' => (string) Str::uuid(), 'user_id' => $user->id, 'role' => AppRole::Admin]);

        return $user;
    }

    public function test_index_sem_page_devolve_array_simples(): void
    {
        Legislacao::factory()->count(3)->create();

        $response = $this->getJson('/api/legislacao');

        $response->assertOk();
        $this->assertIsArray($response->json());
        $this->assertArrayNotHasKey('data', $response->json());
    }

    public function test_index_com_page_devolve_envelope_paginado(): void
    {
        Legislacao::factory()->count(3)->create();

        $response = $this->getJson('/api/legislacao?page=1&per_page=2');

        $response->assertOk()->assertJsonStructure(['data', 'meta' => ['currentPage', 'perPage', 'total', 'lastPage']]);
    }

    public function test_show_encontra_por_slug(): void
    {
        $item = Legislacao::factory()->create(['slug' => 'lei-05-2026']);

        $this->getJson('/api/legislacao/lei-05-2026')->assertOk()->assertJsonPath('id', $item->id);
    }

    public function test_store_requer_autenticacao_e_permissao(): void
    {
        $this->postJson('/api/legislacao', ['num' => '1', 'titulo' => 'X', 'tipo' => 'Lei', 'ano' => '2026'])
            ->assertUnauthorized();
    }

    public function test_store_como_admin_cria_diploma(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->postJson('/api/legislacao', [
            'num' => '12/26',
            'titulo' => 'Lei da Sanidade Animal',
            'tipo' => 'Lei',
            'ano' => '2026',
            'published' => true,
        ]);

        $response->assertCreated()->assertJsonPath('titulo', 'Lei da Sanidade Animal');
    }

    public function test_store_rejeita_tipo_fora_do_enum(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->postJson('/api/legislacao', [
            'num' => '1', 'titulo' => 'X', 'tipo' => 'tipo-invalido', 'ano' => '2026',
        ])->assertStatus(422);
    }
}

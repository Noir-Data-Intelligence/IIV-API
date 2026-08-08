<?php

namespace Tests\Feature;

use App\Enums\AppRole;
use App\Models\Produto;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProdutoTest extends TestCase
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
        $this->getJson('/api/produtos')->assertUnauthorized();
    }

    public function test_index_filtra_por_archived(): void
    {
        Produto::factory()->count(2)->create(['is_archived' => false]);
        Produto::factory()->count(3)->create(['is_archived' => true]);

        $user = $this->comPapel(AppRole::GestorStock);

        $this->actingAs($user)->getJson('/api/produtos?archived=false')->assertJsonPath('meta.total', 2);
        $this->actingAs($user)->getJson('/api/produtos?archived=true')->assertJsonPath('meta.total', 3);
        $this->actingAs($user)->getJson('/api/produtos')->assertJsonPath('meta.total', 5);
    }

    public function test_store_sem_permissao_de_escrita_devolve_403(): void
    {
        $user = $this->comPapel(AppRole::Isv);

        $this->actingAs($user)->postJson('/api/produtos', [
            'name' => 'Vacina X', 'productType' => 'vacina', 'unit' => 'dose',
        ])->assertForbidden();
    }

    public function test_store_como_gestor_stock_cria_produto(): void
    {
        $user = $this->comPapel(AppRole::GestorStock);

        $response = $this->actingAs($user)->postJson('/api/produtos', [
            'name' => 'Vacina Febre Aftosa', 'productType' => 'vacina', 'unit' => 'dose',
        ]);

        $response->assertCreated()
            ->assertJsonPath('name', 'Vacina Febre Aftosa')
            ->assertJsonPath('isArchived', false);
    }

    public function test_archive_e_restore(): void
    {
        $produto = Produto::factory()->create(['is_archived' => false]);
        $user = $this->comPapel(AppRole::GestorStock);

        $this->actingAs($user)->postJson("/api/produtos/{$produto->id}/archive")
            ->assertOk()->assertJsonPath('isArchived', true);

        $this->actingAs($user)->postJson("/api/produtos/{$produto->id}/restore")
            ->assertOk()->assertJsonPath('isArchived', false);
    }

    public function test_destroy_remove_o_produto(): void
    {
        $produto = Produto::factory()->create();
        $user = $this->comPapel(AppRole::GestorStock);

        $this->actingAs($user)->deleteJson("/api/produtos/{$produto->id}")->assertNoContent();
        $this->assertDatabaseMissing('produtos', ['id' => $produto->id]);
    }
}

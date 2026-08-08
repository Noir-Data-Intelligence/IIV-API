<?php

namespace Tests\Feature;

use App\Enums\AppRole;
use App\Models\Plano;
use App\Models\Produto;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PlanoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function gestorStock(): User
    {
        $user = User::factory()->create();
        UserRole::query()->create(['id' => (string) Str::uuid(), 'user_id' => $user->id, 'role' => AppRole::GestorStock]);

        return $user;
    }

    public function test_index_devolve_envelope_paginado_sem_created_at(): void
    {
        Plano::factory()->count(2)->create();
        $user = $this->gestorStock();

        $response = $this->actingAs($user)->getJson('/api/planeamento');

        $response->assertOk()->assertJsonStructure(['data', 'meta' => ['currentPage', 'perPage', 'total', 'lastPage']]);
        $this->assertArrayNotHasKey('createdAt', $response->json('data.0'));
    }

    public function test_store_cria_plano(): void
    {
        $produto = Produto::factory()->create();
        $user = $this->gestorStock();

        $response = $this->actingAs($user)->postJson('/api/planeamento', [
            'productId' => $produto->id,
            'plannedQuantity' => 2000,
            'plannedStart' => '2026-09-01',
            'plannedEnd' => '2026-10-01',
        ]);

        $response->assertCreated()->assertJsonPath('plannedQuantity', 2000)->assertJsonPath('status', 'planeada');
    }

    public function test_destroy_remove_o_plano(): void
    {
        $plano = Plano::factory()->create();
        $user = $this->gestorStock();

        $this->actingAs($user)->deleteJson("/api/planeamento/{$plano->id}")->assertNoContent();
        $this->assertDatabaseMissing('planeamento', ['id' => $plano->id]);
    }
}

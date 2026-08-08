<?php

namespace Tests\Feature;

use App\Enums\AppRole;
use App\Models\Requisicao;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class InqueritoSatisfacaoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_store_e_publico(): void
    {
        $requisicao = Requisicao::factory()->create();

        $response = $this->postJson('/api/inqueritos-satisfacao', [
            'requisicaoId' => $requisicao->id,
            'dimensoes' => ['atendimento' => 'Bom', 'prazo' => 'Regular'],
        ]);

        $response->assertCreated();
        $this->assertDatabaseCount('inqueritos_satisfacao', 1);
    }

    public function test_index_requer_autenticacao(): void
    {
        $this->getJson('/api/inqueritos-satisfacao')->assertUnauthorized();
    }

    public function test_index_devolve_envelope_paginado(): void
    {
        $user = User::factory()->create();
        UserRole::query()->create(['id' => (string) Str::uuid(), 'user_id' => $user->id, 'role' => AppRole::Tecnico]);

        $this->actingAs($user)->getJson('/api/inqueritos-satisfacao')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta' => ['currentPage', 'perPage', 'total', 'lastPage']]);
    }
}

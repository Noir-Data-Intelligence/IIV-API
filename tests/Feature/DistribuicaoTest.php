<?php

namespace Tests\Feature;

use App\Enums\AppRole;
use App\Models\Distribuicao;
use App\Models\Lote;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DistribuicaoTest extends TestCase
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

    public function test_recepcionista_ve_mas_nao_escreve(): void
    {
        $lote = Lote::factory()->create();
        $recepcionista = $this->comPapel(AppRole::Recepcionista);

        $this->actingAs($recepcionista)->getJson('/api/distribuicao')->assertOk();
        $this->actingAs($recepcionista)->postJson('/api/distribuicao', [
            'batchId' => $lote->id, 'destination' => 'Huambo', 'quantity' => 100, 'distributionDate' => '2026-07-01',
        ])->assertForbidden();
    }

    public function test_store_cria_distribuicao(): void
    {
        $lote = Lote::factory()->create();
        $user = $this->comPapel(AppRole::GestorStock);

        $response = $this->actingAs($user)->postJson('/api/distribuicao', [
            'batchId' => $lote->id,
            'destination' => 'Direcção Provincial de Benguela',
            'quantity' => 250,
            'distributionDate' => '2026-07-05',
        ]);

        $response->assertCreated()
            ->assertJsonPath('destination', 'Direcção Provincial de Benguela')
            ->assertJsonPath('quantity', 250);
        $this->assertArrayNotHasKey('createdAt', $response->json());
    }

    public function test_index_filtra_por_batch_id(): void
    {
        $loteA = Lote::factory()->create();
        $loteB = Lote::factory()->create();
        Distribuicao::factory()->create(['batch_id' => $loteA->id]);
        Distribuicao::factory()->create(['batch_id' => $loteB->id]);

        $user = $this->comPapel(AppRole::GestorStock);

        $this->actingAs($user)->getJson("/api/distribuicao?batch_id={$loteA->id}")->assertJsonPath('meta.total', 1);
    }
}

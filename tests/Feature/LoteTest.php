<?php

namespace Tests\Feature;

use App\Enums\AppRole;
use App\Models\Lote;
use App\Models\Produto;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LoteTest extends TestCase
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

    public function test_index_filtra_por_product_id_e_pesquisa(): void
    {
        $produtoA = Produto::factory()->create(['name' => 'Vacina Raiva']);
        $produtoB = Produto::factory()->create(['name' => 'Soro Antitetânico']);
        Lote::factory()->create(['product_id' => $produtoA->id, 'batch_number' => 'LOTE-AAA']);
        Lote::factory()->create(['product_id' => $produtoB->id, 'batch_number' => 'LOTE-BBB']);

        $user = $this->gestorStock();

        $this->actingAs($user)->getJson("/api/lotes?product_id={$produtoA->id}")->assertJsonPath('meta.total', 1);
        $this->actingAs($user)->getJson('/api/lotes?search=Raiva')->assertJsonPath('meta.total', 1);
    }

    public function test_store_valida_expiry_date_apos_production_date(): void
    {
        $produto = Produto::factory()->create();
        $user = $this->gestorStock();

        $this->actingAs($user)->postJson('/api/lotes', [
            'productId' => $produto->id,
            'productionDate' => '2026-06-01',
            'expiryDate' => '2026-01-01',
        ])->assertStatus(422);
    }

    public function test_store_cria_lote(): void
    {
        $produto = Produto::factory()->create();
        $user = $this->gestorStock();

        $response = $this->actingAs($user)->postJson('/api/lotes', [
            'productId' => $produto->id,
            'quantityProduced' => 1000,
            'productionDate' => '2026-06-01',
            'expiryDate' => '2027-06-01',
        ]);

        $response->assertCreated()
            ->assertJsonPath('quantityProduced', 1000)
            ->assertJsonPath('status', 'planeada');
        $this->assertStringStartsWith('LOTE-'.now()->year.'-', $response->json('batchNumber'));
    }

    public function test_store_gera_numero_sequencial_e_ignora_batch_number_do_cliente(): void
    {
        $produto = Produto::factory()->create();
        $user = $this->gestorStock();
        $ano = now()->year;

        $primeiro = $this->actingAs($user)->postJson('/api/lotes', [
            'batchNumber' => 'TENTATIVA-FORJADA', 'productId' => $produto->id,
            'productionDate' => '2026-06-01', 'expiryDate' => '2027-06-01',
        ])->assertCreated()->json();
        $segundo = $this->actingAs($user)->postJson('/api/lotes', [
            'productId' => $produto->id, 'productionDate' => '2026-06-01', 'expiryDate' => '2027-06-01',
        ])->assertCreated()->json();

        $this->assertSame("LOTE-{$ano}-0001", $primeiro['batchNumber']);
        $this->assertSame("LOTE-{$ano}-0002", $segundo['batchNumber']);
    }
}

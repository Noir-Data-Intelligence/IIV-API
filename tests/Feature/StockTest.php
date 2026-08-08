<?php

namespace Tests\Feature;

use App\Enums\AppRole;
use App\Models\StockItem;
use App\Models\StockLocation;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StockTest extends TestCase
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

    public function test_movimento_de_entrada_soma_e_saida_subtrai_a_quantidade_do_item(): void
    {
        $item = StockItem::factory()->create(['quantity' => 10]);
        $user = $this->comPapel(AppRole::GestorStock);

        $this->actingAs($user)->postJson('/api/stock/movimentos', [
            'itemId' => $item->id, 'type' => 'entrada', 'quantity' => 5,
        ])->assertCreated();
        $this->assertEquals(15, $item->fresh()->quantity);

        $this->actingAs($user)->postJson('/api/stock/movimentos', [
            'itemId' => $item->id, 'type' => 'saida', 'quantity' => 3,
        ])->assertCreated();
        $this->assertEquals(12, $item->fresh()->quantity);
    }

    public function test_movimento_de_transferencia_nao_altera_o_total_em_mao(): void
    {
        $item = StockItem::factory()->create(['quantity' => 10]);
        $user = $this->comPapel(AppRole::GestorStock);

        $this->actingAs($user)->postJson('/api/stock/movimentos', [
            'itemId' => $item->id, 'type' => 'transferencia', 'quantity' => 4,
        ])->assertCreated();

        $this->assertEquals(10, $item->fresh()->quantity);
    }

    public function test_quantidade_nunca_desce_abaixo_de_zero(): void
    {
        $item = StockItem::factory()->create(['quantity' => 2]);
        $user = $this->comPapel(AppRole::GestorStock);

        $this->actingAs($user)->postJson('/api/stock/movimentos', [
            'itemId' => $item->id, 'type' => 'saida', 'quantity' => 10,
        ])->assertCreated();

        $this->assertEquals(0, $item->fresh()->quantity);
    }

    public function test_apagar_movimento_reverte_o_efeito_na_quantidade(): void
    {
        $item = StockItem::factory()->create(['quantity' => 10]);
        $user = $this->comPapel(AppRole::GestorStock);

        $movimento = $this->actingAs($user)->postJson('/api/stock/movimentos', [
            'itemId' => $item->id, 'type' => 'entrada', 'quantity' => 5,
        ])->assertCreated()->json();
        $this->assertEquals(15, $item->fresh()->quantity);

        $this->actingAs($user)->deleteJson("/api/stock/movimentos/{$movimento['id']}")->assertNoContent();
        $this->assertEquals(10, $item->fresh()->quantity);
        $this->assertDatabaseMissing('stock_movements', ['id' => $movimento['id']]);
    }

    public function test_apagar_item_arrasta_os_seus_movimentos_em_cascata(): void
    {
        $item = StockItem::factory()->create();
        $movimento = StockMovement::factory()->create(['item_id' => $item->id]);
        $user = $this->comPapel(AppRole::GestorStock);

        $this->actingAs($user)->deleteJson("/api/stock/itens/{$item->id}")->assertNoContent();

        $this->assertDatabaseMissing('stock_items', ['id' => $item->id]);
        $this->assertDatabaseMissing('stock_movements', ['id' => $movimento->id]);
    }

    public function test_apagar_localizacao_liberta_as_referencias_em_vez_de_arrastar(): void
    {
        $location = StockLocation::factory()->create();
        $item = StockItem::factory()->create(['location_id' => $location->id]);
        $user = $this->comPapel(AppRole::GestorStock);

        $this->actingAs($user)->deleteJson("/api/stock/localizacoes/{$location->id}")->assertNoContent();

        $this->assertDatabaseMissing('stock_locations', ['id' => $location->id]);
        $this->assertDatabaseHas('stock_items', ['id' => $item->id, 'location_id' => null]);
    }

    public function test_store_sem_permissao_devolve_403(): void
    {
        $user = $this->comPapel(AppRole::Isv);

        $this->actingAs($user)->postJson('/api/stock/itens', ['name' => 'X'])->assertForbidden();
    }
}

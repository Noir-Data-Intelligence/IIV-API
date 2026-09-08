<?php

namespace Tests\Feature;

use App\Enums\AppRole;
use App\Models\Breeder;
use App\Models\IaCenter;
use App\Models\NitrogenTank;
use App\Models\SemenDose;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Módulo de Inseminação Artificial — único sem envelope de paginação (array simples). */
class InseminacaoTest extends TestCase
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

    public function test_index_dos_centros_devolve_array_simples_sem_paginacao(): void
    {
        IaCenter::factory()->count(3)->create();
        $user = $this->comPapel(AppRole::GestorEstacao);

        $response = $this->actingAs($user)->getJson('/api/inseminacao/centros')->assertOk();

        $this->assertIsArray($response->json());
        $this->assertArrayNotHasKey('meta', $response->json());
        $this->assertCount(3, $response->json());
    }

    public function test_store_reprodutor_tanque_e_dose_rastreavel(): void
    {
        $centro = IaCenter::factory()->create();
        $user = $this->comPapel(AppRole::GestorEstacao);

        $reprodutor = $this->actingAs($user)->postJson('/api/inseminacao/reprodutores', [
            'centerId' => $centro->id, 'species' => 'bovino', 'tag' => 'REP-01',
        ])->assertCreated()->json();

        $tanque = $this->actingAs($user)->postJson('/api/inseminacao/tanques', [
            'centerId' => $centro->id, 'code' => 'TQ-01', 'capacityL' => 50,
        ])->assertCreated()->json();

        $dose = $this->actingAs($user)->postJson('/api/inseminacao/doses', [
            'breederId' => $reprodutor['id'], 'tankId' => $tanque['id'],
            'collectionDate' => '2026-03-01', 'quantity' => 10,
        ])->assertCreated()->json();

        $this->assertSame(10, $dose['availableQuantity']);
        $this->assertSame('A', $dose['qualityGrade']);
    }

    public function test_apagar_centro_com_reprodutores_devolve_erro_restrictondelete(): void
    {
        $centro = IaCenter::factory()->create();
        Breeder::factory()->create(['center_id' => $centro->id]);

        $this->expectException(QueryException::class);
        $centro->delete();
    }

    public function test_apagar_tanque_liberta_a_referencia_nas_doses_em_vez_de_arrastar(): void
    {
        $tanque = NitrogenTank::factory()->create();
        $breeder = Breeder::factory()->create();
        $dose = SemenDose::factory()->create(['tank_id' => $tanque->id, 'breeder_id' => $breeder->id]);
        $user = $this->comPapel(AppRole::GestorEstacao);

        $this->actingAs($user)->deleteJson("/api/inseminacao/tanques/{$tanque->id}")->assertNoContent();

        $this->assertDatabaseHas('semen_doses', ['id' => $dose->id, 'tank_id' => null]);
    }

    public function test_store_tanque_rejeita_data_de_ultima_recarga_futura(): void
    {
        // Regressao da auditoria funcional (seccao 15 — Tanques N2): a data da
        // ultima recarga e um facto passado, nao pode ser posterior a hoje.
        $centro = IaCenter::factory()->create();
        $user = $this->comPapel(AppRole::GestorEstacao);

        $this->actingAs($user)->postJson('/api/inseminacao/tanques', [
            'centerId' => $centro->id,
            'code' => 'TQ-FUT',
            'lastRefillDate' => now()->addDay()->toDateString(),
        ])->assertStatus(422)->assertJsonValidationErrors('lastRefillDate');
    }

    public function test_store_tanque_aceita_data_de_ultima_recarga_de_hoje(): void
    {
        $centro = IaCenter::factory()->create();
        $user = $this->comPapel(AppRole::GestorEstacao);

        $this->actingAs($user)->postJson('/api/inseminacao/tanques', [
            'centerId' => $centro->id,
            'code' => 'TQ-HOJE',
            'lastRefillDate' => now()->toDateString(),
        ])->assertCreated();
    }

    public function test_store_sem_permissao_devolve_403(): void
    {
        $user = $this->comPapel(AppRole::Isv);

        $this->actingAs($user)->postJson('/api/inseminacao/centros', ['name' => 'X'])->assertForbidden();
    }
}

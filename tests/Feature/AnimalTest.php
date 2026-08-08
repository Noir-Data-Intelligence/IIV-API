<?php

namespace Tests\Feature;

use App\Enums\AppRole;
use App\Models\Animal;
use App\Models\AnimalEvent;
use App\Models\AnimalHealthRecord;
use App\Models\Estacao;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AnimalTest extends TestCase
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

    public function test_store_animal_e_eventos_e_registos_sanitarios(): void
    {
        $estacao = Estacao::factory()->create();
        $user = $this->comPapel(AppRole::GestorEstacao);

        $animal = $this->actingAs($user)->postJson('/api/animais', [
            'stationId' => $estacao->id, 'species' => 'bovino', 'tag' => 'AN-0001',
        ])->assertCreated()->json();

        $this->assertSame('activo', $animal['status']);

        $this->actingAs($user)->postJson("/api/animais/{$animal['id']}/eventos", [
            'eventDate' => '2026-01-10', 'eventType' => 'nascimento',
        ])->assertCreated();

        $this->actingAs($user)->postJson("/api/animais/{$animal['id']}/saude", [
            'recordDate' => '2026-02-01', 'recordType' => 'vacina',
        ])->assertCreated();

        $this->actingAs($user)->getJson("/api/animais/{$animal['id']}/eventos")->assertOk()->assertJsonCount(1);
        $this->actingAs($user)->getJson("/api/animais/{$animal['id']}/saude")->assertOk()->assertJsonCount(1);
    }

    public function test_apagar_estacao_com_animais_devolve_erro_restrictondelete(): void
    {
        $estacao = Estacao::factory()->create();
        Animal::factory()->create(['station_id' => $estacao->id]);

        $this->expectException(QueryException::class);
        $estacao->delete();
    }

    public function test_apagar_animal_arrasta_eventos_e_registos_sanitarios_em_cascata(): void
    {
        $animal = Animal::factory()->create();
        $evento = AnimalEvent::factory()->create(['animal_id' => $animal->id]);
        $registo = AnimalHealthRecord::factory()->create(['animal_id' => $animal->id]);
        $user = $this->comPapel(AppRole::GestorEstacao);

        $this->actingAs($user)->deleteJson("/api/animais/{$animal->id}")->assertNoContent();

        $this->assertDatabaseMissing('animals', ['id' => $animal->id]);
        $this->assertDatabaseMissing('animal_events', ['id' => $evento->id]);
        $this->assertDatabaseMissing('animal_health_records', ['id' => $registo->id]);
    }

    public function test_index_filtra_por_estacao_estado_e_especie(): void
    {
        Animal::factory()->create(['species' => 'bovino', 'status' => 'activo']);
        Animal::factory()->create(['species' => 'caprino', 'status' => 'vendido']);
        $user = $this->comPapel(AppRole::GestorEstacao);

        $this->actingAs($user)->getJson('/api/animais?species=bovino')
            ->assertOk()->assertJsonPath('meta.total', 1);
        $this->actingAs($user)->getJson('/api/animais?status=vendido')
            ->assertOk()->assertJsonPath('meta.total', 1);
    }

    public function test_store_sem_permissao_devolve_403(): void
    {
        $user = $this->comPapel(AppRole::Isv);

        $this->actingAs($user)->postJson('/api/animais', ['species' => 'X'])->assertForbidden();
    }
}

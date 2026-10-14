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

    public function test_store_gera_brinco_automaticamente_e_ignora_tag_do_cliente(): void
    {
        // ANL-004: o brinco deve ser sempre gerado pelo servidor, sequencial
        // por ano, mesmo que o cliente tente enviar um valor.
        $estacao = Estacao::factory()->create();
        $user = $this->comPapel(AppRole::GestorEstacao);
        $ano = now()->year;

        $primeiro = $this->actingAs($user)->postJson('/api/animais', [
            'stationId' => $estacao->id, 'species' => 'bovino', 'tag' => 'TENTATIVA-FORJADA',
        ])->assertCreated()->json();
        $segundo = $this->actingAs($user)->postJson('/api/animais', [
            'stationId' => $estacao->id, 'species' => 'caprino',
        ])->assertCreated()->json();

        $this->assertSame("IIV-{$ano}-001", $primeiro['tag']);
        $this->assertSame("IIV-{$ano}-002", $segundo['tag']);
    }

    public function test_evento_de_transferencia_exige_destino(): void
    {
        $animal = Animal::factory()->create();
        $user = $this->comPapel(AppRole::GestorEstacao);

        $this->actingAs($user)->postJson("/api/animais/{$animal->id}/eventos", [
            'eventDate' => '2026-03-01', 'eventType' => 'transferencia',
        ])->assertStatus(422);

        $response = $this->actingAs($user)->postJson("/api/animais/{$animal->id}/eventos", [
            'eventDate' => '2026-03-01', 'eventType' => 'transferencia', 'destination' => 'Estação de Benguela',
        ]);

        $response->assertCreated()->assertJsonPath('destination', 'Estação de Benguela');
    }

    public function test_update_nao_altera_o_brinco_existente(): void
    {
        $animal = Animal::factory()->create(['tag' => 'IIV-2026-001']);
        $user = $this->comPapel(AppRole::GestorEstacao);

        $response = $this->actingAs($user)->putJson("/api/animais/{$animal->id}", [
            'stationId' => $animal->station_id, 'species' => $animal->species, 'tag' => 'OUTRO-VALOR',
        ]);

        $response->assertOk()->assertJsonPath('tag', 'IIV-2026-001');
    }

    public function test_store_sem_permissao_devolve_403(): void
    {
        $user = $this->comPapel(AppRole::Isv);

        $this->actingAs($user)->postJson('/api/animais', ['species' => 'X'])->assertForbidden();
    }

    public function test_estado_reprodutivo_e_aceite_para_femeas(): void
    {
        // CT-012 (relatório de QA, "Aprovado c/ Observação"): o campo Estado
        // Reprodutivo faltava por completo — só faz sentido para fêmeas.
        $estacao = Estacao::factory()->create();
        $user = $this->comPapel(AppRole::GestorEstacao);

        $animal = $this->actingAs($user)->postJson('/api/animais', [
            'stationId' => $estacao->id, 'species' => 'bovino', 'sex' => 'femea',
            'reproductiveStatus' => 'gestante',
        ])->assertCreated()->json();

        $this->assertSame('gestante', $animal['reproductiveStatus']);
    }

    public function test_estado_reprodutivo_e_rejeitado_para_machos(): void
    {
        $estacao = Estacao::factory()->create();
        $user = $this->comPapel(AppRole::GestorEstacao);

        $this->actingAs($user)->postJson('/api/animais', [
            'stationId' => $estacao->id, 'species' => 'bovino', 'sex' => 'macho',
            'reproductiveStatus' => 'gestante',
        ])->assertStatus(422)->assertJsonValidationErrors('reproductiveStatus');
    }

    public function test_estado_reprodutivo_so_aceita_valores_do_dominio(): void
    {
        $estacao = Estacao::factory()->create();
        $user = $this->comPapel(AppRole::GestorEstacao);

        $this->actingAs($user)->postJson('/api/animais', [
            'stationId' => $estacao->id, 'species' => 'bovino', 'sex' => 'femea',
            'reproductiveStatus' => 'valor-invalido',
        ])->assertStatus(422)->assertJsonValidationErrors('reproductiveStatus');
    }

    public function test_update_rejeita_estado_reprodutivo_quando_animal_existente_e_macho(): void
    {
        // `sex` é `sometimes` no update — a validação tem de usar o sexo já
        // gravado no animal quando o pedido não o reenvia.
        $animal = Animal::factory()->create(['sex' => 'macho']);
        $user = $this->comPapel(AppRole::GestorEstacao);

        $this->actingAs($user)->putJson("/api/animais/{$animal->id}", [
            'stationId' => $animal->station_id, 'species' => $animal->species,
            'reproductiveStatus' => 'cio',
        ])->assertStatus(422)->assertJsonValidationErrors('reproductiveStatus');
    }
}

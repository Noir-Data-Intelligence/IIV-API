<?php

namespace Tests\Feature;

use App\Enums\AppRole;
use App\Models\Insumo;
use App\Models\Laboratorio;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Módulo Insumos (`/insumos`) — depende de `laboratorios` via `laboratoryId`.
 * Regressão real: o frontend chamava este módulo desde sempre (Dashboard,
 * PainelDetalhe, Insumos.tsx) e o backend nunca teve nenhuma rota, model ou
 * migration para ele.
 */
class InsumoControllerTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(AppRole $role): User
    {
        $user = User::factory()->create();

        UserRole::query()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'role' => $role,
        ]);

        return $user;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_index_devolve_o_envelope_paginado(): void
    {
        Insumo::factory()->count(3)->create();
        $admin = $this->userWithRole(AppRole::Admin);

        $response = $this->actingAs($admin)->getJson('/api/insumos');

        $response->assertOk()
            ->assertJsonStructure(['data', 'meta' => ['currentPage', 'perPage', 'total', 'lastPage']])
            ->assertJsonCount(3, 'data');
    }

    public function test_index_filtra_por_laboratory_id(): void
    {
        $labA = Laboratorio::factory()->create();
        $labB = Laboratorio::factory()->create();
        Insumo::factory()->create(['laboratorio_id' => $labA->id]);
        Insumo::factory()->create(['laboratorio_id' => $labB->id]);
        $admin = $this->userWithRole(AppRole::Admin);

        $response = $this->actingAs($admin)->getJson("/api/insumos?laboratory_id={$labA->id}");

        $response->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.laboratoryId', $labA->id);
    }

    public function test_store_cria_insumo_como_tecnico(): void
    {
        // "tecnico" tem "insumos" em write na matriz seedada.
        $tecnico = $this->userWithRole(AppRole::Tecnico);
        $laboratorio = Laboratorio::factory()->create();

        $response = $this->actingAs($tecnico)->postJson('/api/insumos', [
            'laboratoryId' => $laboratorio->id,
            'name' => 'Meio de cultura Agar',
            'quantity' => 50,
            'unit' => 'frasco',
            'minStock' => 10,
        ]);

        // JsonResource devolve 201 automaticamente quando o Model subjacente
        // acabou de ser criado (Model::wasRecentlyCreated) — comportamento
        // nativo do Laravel, não precisa de ->setStatusCode(201) explícito.
        $response->assertCreated()
            ->assertJsonPath('name', 'Meio de cultura Agar')
            ->assertJsonPath('laboratoryId', $laboratorio->id)
            ->assertJsonPath('quantity', 50);
        $this->assertDatabaseHas('insumos', ['name' => 'Meio de cultura Agar']);
    }

    public function test_store_sem_permissao_de_escrita_devolve_403(): void
    {
        // "recepcionista" nao tem "insumos" nem em view nem em write.
        $recepcionista = $this->userWithRole(AppRole::Recepcionista);
        $laboratorio = Laboratorio::factory()->create();

        $this->actingAs($recepcionista)
            ->postJson('/api/insumos', [
                'laboratoryId' => $laboratorio->id,
                'name' => 'X',
                'quantity' => 1,
                'unit' => 'unidade',
            ])
            ->assertForbidden();
    }

    public function test_update_altera_quantidade(): void
    {
        $tecnico = $this->userWithRole(AppRole::Tecnico);
        $insumo = Insumo::factory()->create(['quantity' => 10]);

        $response = $this->actingAs($tecnico)->putJson("/api/insumos/{$insumo->id}", [
            'laboratoryId' => $insumo->laboratorio_id,
            'name' => $insumo->name,
            'quantity' => 25,
            'unit' => $insumo->unit,
        ]);

        $response->assertOk()->assertJsonPath('quantity', 25);
    }

    public function test_destroy_remove_o_insumo(): void
    {
        $tecnico = $this->userWithRole(AppRole::Tecnico);
        $insumo = Insumo::factory()->create();

        $this->actingAs($tecnico)->deleteJson("/api/insumos/{$insumo->id}")->assertNoContent();

        $this->assertDatabaseMissing('insumos', ['id' => $insumo->id]);
    }

    public function test_visitante_nao_autenticado_recebe_401(): void
    {
        $this->getJson('/api/insumos')->assertUnauthorized();
    }
}

<?php

namespace Tests\Feature;

use App\Enums\AppRole;
use App\Models\Laboratorio;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LaboratorioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        UserRole::query()->create(['id' => (string) Str::uuid(), 'user_id' => $user->id, 'role' => AppRole::Admin]);

        return $user;
    }

    public function test_index_requer_autenticacao(): void
    {
        $this->getJson('/api/laboratorios')->assertUnauthorized();
    }

    public function test_index_devolve_envelope_paginado(): void
    {
        Laboratorio::factory()->count(3)->create();

        $this->actingAs($this->admin())->getJson('/api/laboratorios')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta' => ['currentPage', 'perPage', 'total', 'lastPage']]);
    }

    public function test_store_sem_permissao_de_escrita_devolve_403(): void
    {
        $tecnico = User::factory()->create();
        UserRole::query()->create(['id' => (string) Str::uuid(), 'user_id' => $tecnico->id, 'role' => AppRole::Tecnico]);

        $this->actingAs($tecnico)->postJson('/api/laboratorios', ['code' => 'XX', 'name' => 'X'])
            ->assertForbidden();
    }

    public function test_store_como_admin_cria_laboratorio(): void
    {
        $response = $this->actingAs($this->admin())->postJson('/api/laboratorios', [
            'name' => 'Parasitologia',
            'type' => 'parasitologia',
            'validadorCount' => 2,
        ]);

        $response->assertCreated()->assertJsonPath('validadorCount', 2);
        $this->assertStringStartsWith('LAB-', $response->json('code'));
    }

    public function test_store_gera_codigo_automaticamente_e_ignora_codigo_do_cliente(): void
    {
        // ANL-similar: codigo deve ser sempre gerado pelo servidor, sequencial,
        // mesmo que o cliente tente enviar um valor.
        $response = $this->actingAs($this->admin())->postJson('/api/laboratorios', [
            'code' => 'TENTATIVA-FORJADA', 'name' => 'Virologia', 'type' => 'virologia',
        ]);

        $response->assertCreated();
        $this->assertNotSame('TENTATIVA-FORJADA', $response->json('code'));
    }

    public function test_store_rejeita_tipo_fora_do_enum(): void
    {
        $this->actingAs($this->admin())->postJson('/api/laboratorios', [
            'name' => 'X', 'type' => 'tipo-invalido',
        ])->assertStatus(422);
    }
}

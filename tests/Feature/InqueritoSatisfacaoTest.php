<?php

namespace Tests\Feature;

use App\Enums\AppRole;
use App\Enums\BoletimInternoStatus;
use App\Models\Amostra;
use App\Models\BoletimInterno;
use App\Models\InqueritoSatisfacao;
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

    /** Requisição com resultados já comunicados — único estado em que o inquérito é aceite (M9). */
    private function requisicaoComunicada(): Requisicao
    {
        $amostra = Amostra::factory()->create();
        BoletimInterno::factory()->create(['amostra_id' => $amostra->id, 'status' => BoletimInternoStatus::Emitido]);

        return $amostra->requisicao;
    }

    public function test_store_e_publico(): void
    {
        $requisicao = $this->requisicaoComunicada();

        $response = $this->postJson('/api/inqueritos-satisfacao', [
            'requisicaoId' => $requisicao->id,
            'dimensoes' => ['atendimento' => 'Bom', 'prazo' => 'Regular'],
        ]);

        $response->assertCreated();
        $this->assertDatabaseCount('inqueritos_satisfacao', 1);
    }

    public function test_store_antes_de_comunicado_devolve_422(): void
    {
        // Regressão (auditoria de segurança, 2026-08-09): sem esta guarda,
        // um UUID de requisição obtido por qualquer via bastava para
        // inundar o sistema com inquéritos falsos antes de sequer haver
        // resultado, já que o endpoint é público.
        $requisicao = Requisicao::factory()->create();

        $this->postJson('/api/inqueritos-satisfacao', [
            'requisicaoId' => $requisicao->id,
            'dimensoes' => ['atendimento' => 'Bom'],
        ])->assertStatus(422);

        $this->assertDatabaseCount('inqueritos_satisfacao', 0);
    }

    public function test_store_repetido_para_a_mesma_requisicao_devolve_422(): void
    {
        $requisicao = $this->requisicaoComunicada();
        InqueritoSatisfacao::query()->create([
            'id' => (string) Str::uuid(),
            'requisicao_id' => $requisicao->id,
            'dimensoes' => ['atendimento' => 'Bom'],
            'created_at' => now(),
        ]);

        $this->postJson('/api/inqueritos-satisfacao', [
            'requisicaoId' => $requisicao->id,
            'dimensoes' => ['atendimento' => 'Mau'],
        ])->assertStatus(422);

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

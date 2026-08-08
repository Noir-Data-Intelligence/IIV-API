<?php

namespace Tests\Feature;

use App\Enums\AppRole;
use App\Models\Laboratorio;
use App\Models\Requisicao;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RequisicaoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function tecnico(): User
    {
        $user = User::factory()->create();
        UserRole::query()->create(['id' => (string) Str::uuid(), 'user_id' => $user->id, 'role' => AppRole::Tecnico]);

        return $user;
    }

    public function test_store_requer_autenticacao(): void
    {
        $this->postJson('/api/requisicoes', [])->assertUnauthorized();
    }

    public function test_store_cria_requisicao_e_lote_de_amostras_numa_so_chamada(): void
    {
        $laboratorio = Laboratorio::factory()->create();
        $tecnico = $this->tecnico();

        $response = $this->actingAs($tecnico)->postJson('/api/requisicoes', [
            'laboratorioId' => $laboratorio->id,
            'tipoSujeito' => 'animal',
            'clienteNome' => 'Fazenda Boa Esperança',
            'consentimento' => true,
            'assinaturaCliente' => 'assinatura-base64',
            'amostras' => [
                ['tipoAmostra' => 'sangue', 'sujeito' => ['especie' => 'Bovino']],
                ['tipoAmostra' => 'fezes', 'sujeito' => ['especie' => 'Bovino']],
            ],
        ]);

        $response->assertCreated();
        $this->assertNotNull($response->json('numero'));
        $this->assertCount(2, $response->json('amostras'));
        $this->assertDatabaseCount('amostras', 2);
    }

    public function test_store_sem_consentimento_devolve_422(): void
    {
        $laboratorio = Laboratorio::factory()->create();

        $this->actingAs($this->tecnico())->postJson('/api/requisicoes', [
            'laboratorioId' => $laboratorio->id,
            'tipoSujeito' => 'animal',
            'clienteNome' => 'X',
            'consentimento' => false,
            'assinaturaCliente' => 'x',
            'amostras' => [['tipoAmostra' => 'sangue']],
        ])->assertStatus(422);
    }

    public function test_show_devolve_requisicao_com_amostras_carregadas(): void
    {
        $requisicao = Requisicao::factory()->create();
        $requisicao->amostras()->create([
            'id' => (string) Str::uuid(),
            'numero' => 'AM-00001',
            'tipo_amostra' => 'sangue',
            'recebida_em' => now(),
        ]);

        $response = $this->actingAs($this->tecnico())->getJson("/api/requisicoes/{$requisicao->id}");

        $response->assertOk()->assertJsonCount(1, 'amostras');
    }
}

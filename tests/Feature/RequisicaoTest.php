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

    /**
     * Rota legada `/requisicoes`: depois do redesenho do Laboratório só a
     * Recepção (CGISC, `lab-recepcao`) regista requisições — o Técnico
     * deixou de o poder fazer (ver test_tecnico_ja_nao_regista_requisicoes).
     */
    private function tecnico(AppRole $role = AppRole::Recepcionista): User
    {
        $user = User::factory()->create();
        UserRole::query()->create(['id' => (string) Str::uuid(), 'user_id' => $user->id, 'role' => $role]);

        return $user;
    }

    public function test_tecnico_ja_nao_regista_requisicoes(): void
    {
        $this->actingAs($this->tecnico(AppRole::Tecnico))->postJson('/api/requisicoes', [])->assertForbidden();
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

    public function test_store_rejeita_tipo_de_amostra_fora_do_enum(): void
    {
        $laboratorio = Laboratorio::factory()->create();

        $this->actingAs($this->tecnico())->postJson('/api/requisicoes', [
            'laboratorioId' => $laboratorio->id,
            'tipoSujeito' => 'animal',
            'clienteNome' => 'X',
            'consentimento' => true,
            'assinaturaCliente' => 'x',
            'amostras' => [['tipoAmostra' => 'tipo-invalido']],
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

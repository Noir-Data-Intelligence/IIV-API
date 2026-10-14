<?php

namespace Tests\Feature;

use App\Enums\AppRole;
use App\Models\Amostra;
use App\Models\Estacao;
use App\Models\Laboratorio;
use App\Models\Requisicao;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Onda 11 (Observatório Veterinário Nacional) — só Fase 1 da RDA §8.4.
 * Sem contrato de frontend a verificar (primeira Onda sem mock a espelhar).
 */
class ObservatorioTest extends TestCase
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

    public function test_store_estacao_com_coordenadas(): void
    {
        $user = $this->comPapel(AppRole::GestorEstacao);

        $estacao = $this->actingAs($user)->postJson('/api/estacoes', [
            'name' => 'Estação do Huambo', 'latitude' => -12.7761, 'longitude' => 15.7392,
        ])->assertCreated()->json();

        $this->assertSame(-12.7761, $estacao['latitude']);
        $this->assertSame(15.7392, $estacao['longitude']);
    }

    public function test_store_requisicao_com_coordenadas_na_amostra(): void
    {
        $laboratorio = Laboratorio::factory()->create();
        $user = $this->comPapel(AppRole::Recepcionista);

        $response = $this->actingAs($user)->postJson('/api/requisicoes', [
            'laboratorioId' => $laboratorio->id,
            'tipoSujeito' => 'animal',
            'clienteNome' => 'Fazenda Boa Esperança',
            'consentimento' => true,
            'assinaturaCliente' => 'assinatura-base64',
            'amostras' => [
                ['tipoAmostra' => 'sangue', 'latitude' => -8.8383, 'longitude' => 13.2344],
            ],
        ])->assertCreated();

        $this->assertSame(-8.8383, $response->json('amostras.0.latitude'));
        $this->assertSame(13.2344, $response->json('amostras.0.longitude'));
    }

    public function test_mapa_devolve_apenas_estacoes_e_colheitas_georreferenciadas(): void
    {
        Estacao::factory()->create(['latitude' => -12.7761, 'longitude' => 15.7392]);
        Estacao::factory()->create(['latitude' => null, 'longitude' => null]);

        $requisicao = Requisicao::factory()->create();
        Amostra::factory()->create(['requisicao_id' => $requisicao->id, 'latitude' => -8.8383, 'longitude' => 13.2344]);
        Amostra::factory()->create(['requisicao_id' => $requisicao->id, 'latitude' => null, 'longitude' => null]);

        $user = $this->comPapel(AppRole::DirectorLaboratorio);

        $this->actingAs($user)->getJson('/api/observatorio/mapa')->assertOk()
            ->assertJsonCount(1, 'estacoes')
            ->assertJsonCount(1, 'colheitas')
            ->assertJsonPath('colheitas.0.laboratorioNome', $requisicao->laboratorio->name);
    }

    public function test_isv_tem_acesso_de_leitura_ao_observatorio(): void
    {
        $user = $this->comPapel(AppRole::Isv);

        $this->actingAs($user)->getJson('/api/observatorio/mapa')->assertOk();
    }

    public function test_papel_sem_acesso_devolve_403(): void
    {
        $user = $this->comPapel(AppRole::GestorFinanceiro);

        $this->actingAs($user)->getJson('/api/observatorio/mapa')->assertForbidden();
    }
}

<?php

namespace Tests\Feature;

use App\Enums\AppRole;
use App\Models\Amostra;
use App\Models\CriterioRejeicao;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Rotas LEGADAS `/amostras`. O fluxo antigo (aceitar num clique criando o BI
 * já em análise; rejeitar com uma só assinatura) era o próprio DEF-001 — foi
 * retirado (410 Gone). O fluxo novo está coberto em LabRecepcaoTest e
 * LabFluxoCompletoTest.
 */
class AmostraTest extends TestCase
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

    public function test_aceitar_legado_ja_nao_abre_boletim_interno_num_clique(): void
    {
        $amostra = Amostra::factory()->create();

        $this->actingAs($this->comPapel(AppRole::Recepcionista))->postJson("/api/amostras/{$amostra->id}/aceitar")
            ->assertStatus(410);
        $this->assertDatabaseCount('boletins_internos', 0);
    }

    public function test_rejeitar_legado_retirado(): void
    {
        $amostra = Amostra::factory()->create();

        $this->actingAs($this->comPapel(AppRole::Recepcionista))->postJson("/api/amostras/{$amostra->id}/rejeitar", [
            'criterioRejeicaoId' => CriterioRejeicao::factory()->create()->id,
            'assinaturaResponsavel' => 'x',
        ])->assertStatus(410);
        $this->assertDatabaseCount('amostra_rejeicoes', 0);
    }

    public function test_tecnico_nao_tem_acesso_as_accoes_de_recepcao(): void
    {
        $amostra = Amostra::factory()->create();

        $this->actingAs($this->comPapel(AppRole::Tecnico))->postJson("/api/amostras/{$amostra->id}/aceitar")
            ->assertForbidden();
    }

    public function test_leitura_legada_continua_a_funcionar(): void
    {
        $amostra = Amostra::factory()->create();
        $user = $this->comPapel(AppRole::Recepcionista);

        $this->actingAs($user)->getJson('/api/amostras')->assertOk()->assertJsonCount(1, 'data');
        $this->actingAs($user)->getJson("/api/amostras/{$amostra->id}")->assertOk()->assertJsonPath('status', 'recebida');
    }
}

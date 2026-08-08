<?php

namespace Tests\Feature;

use App\Enums\AppRole;
use App\Enums\BoletimStatus;
use App\Models\Amostra;
use App\Models\BoletimAnalise;
use App\Models\BoletimInterno;
use App\Models\Laboratorio;
use App\Models\Requisicao;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BoletimTest extends TestCase
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

    /** Cria um boletim (com resultado já registado) sobre um laboratório à escolha. */
    private function boletimEmValidacao(Laboratorio $laboratorio, array $resultado = ['parametros' => ['ph' => 7]]): BoletimInterno
    {
        $requisicao = Requisicao::factory()->create(['laboratorio_id' => $laboratorio->id]);
        $amostra = Amostra::factory()->create(['requisicao_id' => $requisicao->id]);
        $boletim = BoletimInterno::factory()->create(['amostra_id' => $amostra->id]);
        BoletimAnalise::query()->create([
            'id' => (string) Str::uuid(),
            'boletim_interno_id' => $boletim->id,
            'resultado' => $resultado,
        ]);
        $boletim->update(['status' => BoletimStatus::ResultadoRegistado]);

        return $boletim->refresh();
    }

    public function test_registar_resultado_muda_status_para_resultado_registado(): void
    {
        $amostra = Amostra::factory()->create();
        $boletim = BoletimInterno::factory()->create(['amostra_id' => $amostra->id]);

        $response = $this->actingAs($this->comPapel(AppRole::Tecnico))
            ->putJson("/api/boletins/{$boletim->id}/resultado", ['resultado' => ['parametros' => ['ph' => 7]]]);

        $response->assertOk()->assertJsonPath('status', BoletimStatus::ResultadoRegistado->value);
    }

    public function test_validacao_dupla_com_2_validadores_distintos_aprova_o_boletim(): void
    {
        $laboratorio = Laboratorio::factory()->create(['validador_count' => 2]);
        $boletim = $this->boletimEmValidacao($laboratorio);

        $v1 = $this->comPapel(AppRole::Tecnico);
        $v2 = $this->comPapel(AppRole::DirectorLaboratorio);

        $this->actingAs($v1)->postJson("/api/boletins/{$boletim->id}/validar")
            ->assertOk()->assertJsonPath('status', BoletimStatus::EmValidacao->value);

        $response = $this->actingAs($v2)->postJson("/api/boletins/{$boletim->id}/validar");
        $response->assertOk()->assertJsonPath('status', BoletimStatus::Aprovado->value);
        $this->assertNotNull($response->json('boletimAnalise.validadoEm'));
    }

    public function test_mesmo_validador_nao_pode_validar_duas_vezes(): void
    {
        $laboratorio = Laboratorio::factory()->create(['validador_count' => 2]);
        $boletim = $this->boletimEmValidacao($laboratorio);
        $v1 = $this->comPapel(AppRole::Tecnico);

        $this->actingAs($v1)->postJson("/api/boletins/{$boletim->id}/validar")->assertOk();
        $this->actingAs($v1)->postJson("/api/boletins/{$boletim->id}/validar")->assertStatus(422);
    }

    public function test_bacteriologia_exige_3_validadores_da_lista_nomeada(): void
    {
        // 'foraDaLista' também é admin (para passar o gate RBAC de escrita em
        // 'resultados'), mas fica de fora do array `validadores_nomeados` —
        // isola a regra de negócio (lista nomeada) da autorização RBAC.
        $foraDaLista = $this->comPapel(AppRole::Admin);
        $nomeado1 = $this->comPapel(AppRole::Tecnico);
        $nomeado2 = $this->comPapel(AppRole::DirectorLaboratorio);
        $nomeado3 = $this->comPapel(AppRole::Admin);

        $laboratorio = Laboratorio::factory()->create([
            'code' => 'AB',
            'validador_count' => 3,
            'validadores_nomeados' => [$nomeado1->id, $nomeado2->id, $nomeado3->id],
        ]);
        $boletim = $this->boletimEmValidacao($laboratorio);

        $this->actingAs($foraDaLista)->postJson("/api/boletins/{$boletim->id}/validar")->assertStatus(422);

        $this->actingAs($nomeado1)->postJson("/api/boletins/{$boletim->id}/validar")->assertOk();
        $this->actingAs($nomeado2)->postJson("/api/boletins/{$boletim->id}/validar")
            ->assertOk()->assertJsonPath('status', BoletimStatus::EmValidacao->value);
        $response = $this->actingAs($nomeado3)->postJson("/api/boletins/{$boletim->id}/validar");
        $response->assertOk()->assertJsonPath('status', BoletimStatus::Aprovado->value);
    }

    public function test_bromatologia_bloqueia_aprovacao_se_salmonela_diferente_de_zero(): void
    {
        $laboratorio = Laboratorio::factory()->create(['code' => 'BR', 'validador_count' => 2]);
        $boletim = $this->boletimEmValidacao($laboratorio, ['parametros' => ['salmonela' => 1]]);

        $v1 = $this->comPapel(AppRole::Tecnico);
        $v2 = $this->comPapel(AppRole::DirectorLaboratorio);

        $this->actingAs($v1)->postJson("/api/boletins/{$boletim->id}/validar")->assertOk();
        $this->actingAs($v2)->postJson("/api/boletins/{$boletim->id}/validar")->assertStatus(422);

        $this->assertSame(BoletimStatus::EmValidacao, $boletim->refresh()->status);
    }

    public function test_bromatologia_aprova_quando_salmonela_e_zero(): void
    {
        $laboratorio = Laboratorio::factory()->create(['code' => 'BR', 'validador_count' => 2]);
        $boletim = $this->boletimEmValidacao($laboratorio, ['parametros' => ['salmonela' => 0]]);

        $v1 = $this->comPapel(AppRole::Tecnico);
        $v2 = $this->comPapel(AppRole::DirectorLaboratorio);

        $this->actingAs($v1)->postJson("/api/boletins/{$boletim->id}/validar")->assertOk();
        $this->actingAs($v2)->postJson("/api/boletins/{$boletim->id}/validar")
            ->assertOk()->assertJsonPath('status', BoletimStatus::Aprovado->value);
    }

    public function test_comunicar_exige_boletim_aprovado(): void
    {
        $laboratorio = Laboratorio::factory()->create(['validador_count' => 2]);
        $boletim = $this->boletimEmValidacao($laboratorio);

        $this->actingAs($this->comPapel(AppRole::Admin))
            ->postJson("/api/boletins/{$boletim->id}/comunicar")
            ->assertStatus(422);
    }

    public function test_comunicar_apos_aprovado_marca_comunicado(): void
    {
        $laboratorio = Laboratorio::factory()->create(['validador_count' => 2]);
        $boletim = $this->boletimEmValidacao($laboratorio);

        $this->actingAs($this->comPapel(AppRole::Tecnico))->postJson("/api/boletins/{$boletim->id}/validar");
        $this->actingAs($this->comPapel(AppRole::DirectorLaboratorio))->postJson("/api/boletins/{$boletim->id}/validar");

        $response = $this->actingAs($this->comPapel(AppRole::Admin))
            ->postJson("/api/boletins/{$boletim->id}/comunicar");

        $response->assertOk()->assertJsonPath('status', BoletimStatus::Comunicado->value);
        $this->assertNotNull($response->json('boletimAnalise.comunicadoEm'));
    }
}

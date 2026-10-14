<?php

namespace Tests\Feature;

use App\Enums\AppRole;
use App\Enums\BoletimInternoStatus;
use App\Enums\RequisicaoStatus;
use App\Models\BoletimInterno;
use App\Models\CriterioRejeicao;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\LabFixtures;
use Tests\TestCase;

/** Fase 2 — EncaminhamentoService + AreaWorkflowService via API (DEF-002). */
class LabAreaWorkflowTest extends TestCase
{
    use LabFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_bi_nao_pode_ser_recebido_na_area_sem_ter_sido_encaminhado(): void
    {
        $ab = $this->area('AB');
        $tec = $this->membroDe($ab);
        $requisicao = $this->processoAberto([$this->exame($ab)]);
        $bi = $this->biDe($requisicao, $ab);

        $this->actingAs($tec)->postJson("/api/lab/boletins-internos/{$bi->id}/receber-na-area")
            ->assertStatus(422)->assertJsonValidationErrors('status');
        $this->actingAs($tec)->postJson("/api/lab/boletins-internos/{$bi->id}/iniciar-ensaio")->assertStatus(422);
        $this->assertSame(BoletimInternoStatus::Gerado, $bi->refresh()->status);
    }

    public function test_director_de_laboratorio_recebe_em_qualquer_area_mas_nao_tem_escrita_de_area(): void
    {
        $ab = $this->area('AB');
        $requisicao = $this->processoAberto([$this->exame($ab)]);
        $bi = $this->biDe($requisicao, $ab);
        $this->actingAs($this->comPapel(AppRole::Recepcionista))->postJson("/api/lab/boletins-internos/{$bi->id}/encaminhar")->assertOk();

        // Director de Laboratório: vê a fila (supervisão) mas a escrita de área é do técnico (RBAC lab-area).
        $director = $this->comPapel(AppRole::DirectorLaboratorio);
        $this->actingAs($director)->getJson('/api/lab/areas/AB/fila')->assertOk()->assertJsonCount(1, 'porReceber');
        $this->actingAs($director)->postJson("/api/lab/boletins-internos/{$bi->id}/receber-na-area")->assertForbidden();
    }

    public function test_fila_da_area_so_para_membros(): void
    {
        $ab = $this->area('AB');
        $as = $this->area('AS');
        $tecAB = $this->membroDe($ab);
        $tecAS = $this->membroDe($as);
        $requisicao = $this->processoAberto([$this->exame($ab)]);
        $bi = $this->biDe($requisicao, $ab);
        $this->biEmAnalise($bi, $tecAB);

        $this->actingAs($tecAS)->getJson('/api/lab/areas/AB/fila')->assertForbidden();
        $this->actingAs($tecAB)->getJson('/api/lab/areas/AB/fila')
            ->assertOk()->assertJsonCount(1, 'emAnalise')->assertJsonPath('emAnalise.0.numero', $bi->numero);
        $this->actingAs($tecAB)->getJson('/api/lab/areas')->assertOk()->assertJsonCount(1)->assertJsonPath('0.code', 'AB');
        $this->actingAs($tecAS)->getJson("/api/lab/boletins-internos/{$bi->id}")->assertForbidden();
        $this->actingAs($tecAB)->getJson("/api/lab/boletins-internos/{$bi->id}")->assertOk()->assertJsonCount(1, 'amostras');
    }

    public function test_ensaio_valida_resultado_contra_o_catalogo(): void
    {
        $ab = $this->area('AB');
        $tec = $this->membroDe($ab);
        $exame = $this->exame($ab, [
            ['chave' => 'crescimento', 'tipo' => 'opcao', 'opcoes' => ['sem_crescimento', 'abundante']],
            ['chave' => 'contagem', 'tipo' => 'numero'],
            ['chave' => 'nota', 'tipo' => 'texto', 'obrigatorio' => false],
        ]);
        $outraArea = $this->exame($this->area('AS'));
        $requisicao = $this->processoAberto([$exame]);
        $bi = $this->biEmAnalise($this->biDe($requisicao, $ab), $tec);
        $amostra = $bi->amostras->first();
        $url = "/api/lab/boletins-internos/{$bi->id}/ensaios";

        $this->actingAs($tec)->putJson($url, ['amostraId' => $amostra->id, 'exameId' => $exame->id, 'resultado' => ['crescimento' => 'abundante']])
            ->assertStatus(422)->assertJsonValidationErrors('resultado.contagem');
        $this->actingAs($tec)->putJson($url, ['amostraId' => $amostra->id, 'exameId' => $exame->id, 'resultado' => ['crescimento' => 'talvez', 'contagem' => 3]])
            ->assertStatus(422)->assertJsonValidationErrors('resultado.crescimento');
        $this->actingAs($tec)->putJson($url, ['amostraId' => $amostra->id, 'exameId' => $exame->id, 'resultado' => ['crescimento' => 'abundante', 'contagem' => 3, 'xpto' => 1]])
            ->assertStatus(422)->assertJsonValidationErrors('resultado.xpto');
        $this->actingAs($tec)->putJson($url, ['amostraId' => $amostra->id, 'exameId' => $outraArea->id, 'resultado' => ['valor' => 1]])
            ->assertStatus(422)->assertJsonValidationErrors('exameId');

        $this->actingAs($tec)->postJson("/api/lab/boletins-internos/{$bi->id}/submeter-validacao")
            ->assertStatus(422)->assertJsonValidationErrors('ensaios'); // resultados em falta

        $this->actingAs($tec)->putJson($url, [
            'amostraId' => $amostra->id, 'exameId' => $exame->id, 'equipamentoId' => 'EQ-7', 'loteReagente' => 'L-1',
            'resultado' => ['crescimento' => 'abundante', 'contagem' => 3], 'resultadoQualitativo' => 'positivo',
        ])->assertSuccessful()->assertJsonPath('executorId', $tec->id)->assertJsonPath('equipamentoId', 'EQ-7');

        $this->actingAs($tec)->postJson("/api/lab/boletins-internos/{$bi->id}/submeter-validacao")
            ->assertOk()->assertJsonPath('status', 'resultado_registado')->assertJsonPath('resultadoGlobal', 'positivo');
    }

    public function test_tecnico_de_outra_area_nao_regista_ensaios(): void
    {
        $ab = $this->area('AB');
        $tec = $this->membroDe($ab);
        $intruso = $this->membroDe($this->area('AS'));
        $exame = $this->exame($ab);
        $bi = $this->biEmAnalise($this->biDe($this->processoAberto([$exame]), $ab), $tec);

        $this->actingAs($intruso)->putJson("/api/lab/boletins-internos/{$bi->id}/ensaios", [
            'amostraId' => $bi->amostras->first()->id, 'exameId' => $exame->id, 'resultado' => ['valor' => 1],
        ])->assertForbidden();
    }

    public function test_bromatologia_so_valida_com_salmonela_zero(): void
    {
        $br = $this->area('BR', 2);
        $tec = $this->membroDe($br);
        $exame = $this->exame($br, [['chave' => 'salmonela', 'tipo' => 'numero']]);
        $bi = $this->biEmAnalise($this->biDe($this->processoAberto([$exame]), $br), $tec);
        $this->biComResultados($bi, $tec, $exame, ['salmonela' => 1], 'positivo');

        $this->actingAs($this->comPapel(AppRole::ResponsavelQualidade))->postJson("/api/lab/boletins-internos/{$bi->id}/validar")->assertOk();
        $this->actingAs($this->comPapel(AppRole::DirectorLaboratorio))->postJson("/api/lab/boletins-internos/{$bi->id}/validar")
            ->assertStatus(422)->assertJsonValidationErrors('resultado');

        $this->assertSame(BoletimInternoStatus::EmValidacao, $bi->refresh()->status);
        $this->assertCount(1, $bi->validacoes);
    }

    public function test_bromatologia_valida_quando_salmonela_e_zero(): void
    {
        $br = $this->area('BR', 2);
        $tec = $this->membroDe($br);
        $exame = $this->exame($br, [['chave' => 'salmonela', 'tipo' => 'numero']]);
        $bi = $this->biEmAnalise($this->biDe($this->processoAberto([$exame]), $br), $tec);
        $this->biComResultados($bi, $tec, $exame, ['salmonela' => 0]);

        $this->actingAs($this->comPapel(AppRole::ResponsavelQualidade))->postJson("/api/lab/boletins-internos/{$bi->id}/validar")->assertOk();
        $this->actingAs($this->comPapel(AppRole::DirectorLaboratorio))->postJson("/api/lab/boletins-internos/{$bi->id}/validar")
            ->assertOk()->assertJsonPath('status', 'validado');
    }

    public function test_tecnico_da_area_pode_ser_segundo_interveniente_mas_nao_de_outra_area(): void
    {
        $as = $this->area('AS', 2);
        $executor = $this->membroDe($as);
        $colega = $this->membroDe($as);
        $deFora = $this->membroDe($this->area('AP'));
        $exame = $this->exame($as);
        $bi = $this->biEmAnalise($this->biDe($this->processoAberto([$exame]), $as), $executor);
        $this->biComResultados($bi, $executor, $exame);

        $this->actingAs($deFora)->postJson("/api/lab/boletins-internos/{$bi->id}/validar")->assertForbidden();
        $this->actingAs($colega)->postJson("/api/lab/boletins-internos/{$bi->id}/validar")
            ->assertOk()->assertJsonPath('validacoes.0.tipo', 'segundo');
    }

    public function test_rejeicao_de_todas_as_amostras_na_area_termina_o_bi(): void
    {
        $ab = $this->area('AB');
        $tec = $this->membroDe($ab);
        $colega = $this->membroDe($ab);
        $requisicao = $this->processoAberto([$this->exame($ab)]);
        $bi = $this->biDe($requisicao, $ab);
        $this->actingAs($this->comPapel(AppRole::Recepcionista))->postJson("/api/lab/boletins-internos/{$bi->id}/encaminhar");
        $this->actingAs($tec)->postJson("/api/lab/boletins-internos/{$bi->id}/receber-na-area")->assertOk();

        $this->actingAs($tec)->postJson("/api/lab/boletins-internos/{$bi->id}/rejeitar-amostras", [
            'amostraIds' => [$bi->amostras->first()->id],
            'criterioRejeicaoId' => CriterioRejeicao::factory()->create()->id,
            'assinaturaResponsavel' => 'a', 'segundoResponsavelId' => $colega->id, 'assinaturaSegundo' => 'b',
        ])->assertOk()->assertJsonPath('status', BoletimInternoStatus::RejeitadoNaArea->value);

        $this->assertDatabaseHas('amostra_rejeicoes', ['fase' => 'area', 'boletim_interno_id' => $bi->id, 'laboratorio_id' => $ab->id]);
        $this->assertSame(RequisicaoStatus::Emitida, $requisicao->refresh()->status); // único BI terminado
    }

    public function test_reencaminhar_cria_novo_bi_noutra_area(): void
    {
        $ab = $this->area('AB');
        $bm = $this->area('BM');
        $tec = $this->membroDe($ab);
        $exameBM = $this->exame($bm);
        $requisicao = $this->processoAberto([$this->exame($ab)]);
        $bi = $this->biEmAnalise($this->biDe($requisicao, $ab), $tec);

        $response = $this->actingAs($tec)->postJson("/api/lab/boletins-internos/{$bi->id}/reencaminhar", [
            'laboratorioId' => $bm->id, 'exameIds' => [$exameBM->id], 'motivo' => 'Confirmação por PCR', 'duplicar' => true,
        ]);
        $response->assertCreated()->assertJsonPath('status', 'gerado')->assertJsonPath('biOrigemId', $bi->id);
        $this->assertSame("{$requisicao->pat_numero}/BM", $response->json('numero'));

        $novo = BoletimInterno::query()->findOrFail($response->json('id'));
        $subAmostra = $novo->amostras->first();
        $this->assertSame($bi->amostras->first()->id, $subAmostra->amostra_origem_id);
        $this->assertSame(BoletimInternoStatus::EmAnalise, $bi->refresh()->status); // origem segue o seu fluxo
        $this->assertDatabaseHas('amostra_movimentos', ['accao' => 'reencaminhada', 'boletim_interno_id' => $novo->id]);
    }

    public function test_devolucao_para_correccao_anula_validacoes(): void
    {
        $as = $this->area('AS', 2);
        $tec = $this->membroDe($as);
        $exame = $this->exame($as);
        $bi = $this->biEmAnalise($this->biDe($this->processoAberto([$exame]), $as), $tec);
        $this->biComResultados($bi, $tec, $exame);
        $q = $this->comPapel(AppRole::ResponsavelQualidade);
        $d = $this->comPapel(AppRole::DirectorLaboratorio);
        $this->actingAs($q)->postJson("/api/lab/boletins-internos/{$bi->id}/validar")->assertOk();
        $this->actingAs($d)->postJson("/api/lab/boletins-internos/{$bi->id}/validar")->assertOk();
        $this->actingAs($tec)->postJson("/api/lab/boletins-internos/{$bi->id}/devolver")->assertOk();
        $cgisc = $this->comPapel(AppRole::Recepcionista);
        $this->actingAs($cgisc)->postJson("/api/lab/boletins-internos/{$bi->id}/receber-devolucao")->assertOk();

        $this->actingAs($cgisc)->postJson("/api/lab/boletins-internos/{$bi->id}/devolver-correccao", ['motivo' => 'Unidade errada'])
            ->assertOk()->assertJsonPath('status', 'em_analise')->assertJsonPath('motivoCorreccao', 'Unidade errada');
        $this->assertCount(0, $bi->refresh()->validacoes);

        // Volta a passar pelo ciclo completo — os mesmos validadores podem voltar a validar.
        $this->actingAs($tec)->postJson("/api/lab/boletins-internos/{$bi->id}/submeter-validacao")->assertOk();
        $this->actingAs($q)->postJson("/api/lab/boletins-internos/{$bi->id}/validar")->assertOk();
        $this->actingAs($d)->postJson("/api/lab/boletins-internos/{$bi->id}/validar")->assertOk()->assertJsonPath('status', 'validado');
    }

    public function test_devolver_a_cgisc_exige_bi_validado(): void
    {
        $as = $this->area('AS');
        $tec = $this->membroDe($as);
        $bi = $this->biEmAnalise($this->biDe($this->processoAberto([$this->exame($as)]), $as), $tec);

        $this->actingAs($tec)->postJson("/api/lab/boletins-internos/{$bi->id}/devolver")->assertStatus(422);
    }
}

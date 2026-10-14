<?php

namespace Tests\Feature;

use App\Enums\AmostraStatus;
use App\Enums\AppRole;
use App\Enums\BoletimInternoStatus;
use App\Enums\RequisicaoStatus;
use App\Models\Amostra;
use App\Models\AmostraMovimento;
use App\Models\BoletimAnalise;
use App\Models\CriterioRejeicao;
use App\Models\Requisicao;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\LabFixtures;
use Tests\TestCase;

/**
 * Teste E2E do redesenho do Laboratório (DEF-001/002/003): 1 requisição com
 * 2 amostras e exames de 2 áreas (AB + AS) -> 1 amostra rejeitada na 2ª
 * conferência -> processo aberto gera 2 BI -> técnico de AS não recebe o BI
 * de AB (403) -> executor não valida o seu ensaio (422) -> AB exige os 3
 * validadores nomeados -> devolução -> recepção na CGISC -> emissão de 2 BA
 * -> processo encerra; um Técnico que tenta emitir recebe 403.
 */
class LabFluxoCompletoTest extends TestCase
{
    use LabFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_fluxo_completo_recepcao_areas_emissao(): void
    {
        // --- Preparação: áreas, equipas, validadores, catálogo -------------
        $r1 = $this->comPapel(AppRole::Recepcionista);
        $r2 = $this->comPapel(AppRole::Recepcionista);
        $director = $this->comPapel(AppRole::DirectorLaboratorio);
        $qualidade = $this->comPapel(AppRole::ResponsavelQualidade);

        $as = $this->area('AS', 2);
        $tecAS = $this->membroDe($as);

        $ab = $this->area('AB', 3);
        $tecAB = $this->membroDe($ab);                 // executor
        $nomeadoTecnico = $this->membroDe($ab);        // 2º técnico da área, nomeado
        $foraDaLista = $this->membroDe($ab);           // técnico da área, NÃO nomeado
        $ab->update(['validadores_nomeados' => [$nomeadoTecnico->id, $director->id, $qualidade->id]]);

        $exameAB = $this->exame($ab, [['chave' => 'crescimento', 'tipo' => 'opcao', 'opcoes' => ['sem_crescimento', 'abundante']]]);
        $exameAS = $this->exame($as, [['chave' => 'reaccao', 'tipo' => 'opcao', 'opcoes' => ['negativo', 'positivo']]]);
        $criterio = CriterioRejeicao::factory()->create();

        // --- Fase 1: Recepção (CGISC) -------------------------------------
        $resp = $this->actingAs($r1)->postJson('/api/lab/recepcao/requisicoes',
            $this->payloadRequisicao([['exameId' => $exameAB->id], ['exameId' => $exameAS->id]], 2));
        $resp->assertCreated()
            ->assertJsonPath('status', RequisicaoStatus::Registada->value)
            ->assertJsonPath('areas', ['AB', 'AS']); // derivadas dos exames, sem select de laboratório
        $requisicao = Requisicao::query()->findOrFail($resp->json('id'));
        [$a1, $a2] = $requisicao->amostras()->orderBy('numero')->get()->all();

        // A1: dupla conferência conforme por 2 técnicos diferentes -> aceite
        $this->conferir($r1, $a1)->assertCreated();
        $this->conferir($r1, $a1)->assertStatus(422); // o mesmo técnico não pode confirmar
        $this->conferir($r2, $a1)->assertCreated();
        $this->assertSame(AmostraStatus::Aceite, $a1->refresh()->status);

        // A2: 2ª conferência não conforme -> Termo de Rejeição com 2 assinaturas
        $this->conferir($r1, $a2)->assertCreated();
        $this->conferir($r2, $a2, conforme: false)->assertCreated();
        $this->assertSame(AmostraStatus::EmConferencia, $a2->refresh()->status);
        $this->actingAs($r1)->postJson("/api/lab/recepcao/amostras/{$a2->id}/rejeitar", [
            'criterioRejeicaoId' => $criterio->id, 'assinaturaResponsavel' => 'r1',
            'segundoResponsavelId' => $r1->id, 'assinaturaSegundo' => 'r1',
        ])->assertStatus(422); // 2 assinaturas do mesmo responsável
        $this->actingAs($r1)->postJson("/api/lab/recepcao/amostras/{$a2->id}/rejeitar", [
            'criterioRejeicaoId' => $criterio->id, 'assinaturaResponsavel' => 'r1',
            'segundoResponsavelId' => $r2->id, 'assinaturaSegundo' => 'r2', 'detalhe' => 'Conservação inadequada',
        ])->assertOk()->assertJsonPath('status', AmostraStatus::Rejeitada->value);

        // Abrir processo: PAT + 2 BI (1 por área), só com a amostra aceite
        $this->actingAs($r1)->postJson("/api/lab/recepcao/requisicoes/{$requisicao->id}/abrir-processo")
            ->assertOk()->assertJsonPath('status', RequisicaoStatus::ProcessoAberto->value);
        $requisicao->refresh();
        $this->assertMatchesRegularExpression('/^PAT-\d{3}-\d{4}$/', $requisicao->pat_numero);
        $this->assertCount(2, $requisicao->boletinsInternos);
        $biAB = $this->biDe($requisicao, $ab);
        $biAS = $this->biDe($requisicao, $as);
        $this->assertSame("{$requisicao->pat_numero}/AB", $biAB->numero);
        $this->assertSame(BoletimInternoStatus::Gerado, $biAB->status);
        $this->assertEquals([$a1->id], $biAB->amostras->pluck('id')->all());
        $this->assertSame("{$requisicao->pat_numero}-A1", $a1->refresh()->codigo_barras);

        // --- Fase 2: encaminhamento e recepção na área --------------------
        $this->actingAs($tecAB)->postJson("/api/lab/requisicoes/{$requisicao->id}/encaminhar")->assertForbidden(); // só a CGISC encaminha
        $this->actingAs($r1)->postJson("/api/lab/requisicoes/{$requisicao->id}/encaminhar")->assertOk();
        $this->assertSame(BoletimInternoStatus::EmTransito, $biAB->refresh()->status);

        $this->actingAs($tecAS)->postJson("/api/lab/boletins-internos/{$biAB->id}/receber-na-area")->assertForbidden(); // AS não recebe o BI de AB
        $this->actingAs($r1)->postJson("/api/lab/boletins-internos/{$biAB->id}/receber-na-area")->assertForbidden(); // a recepção não processa na área
        $this->actingAs($tecAB)->postJson("/api/lab/boletins-internos/{$biAB->id}/receber-na-area")
            ->assertOk()->assertJsonPath('status', BoletimInternoStatus::RecebidoArea->value);
        $this->actingAs($tecAS)->postJson("/api/lab/boletins-internos/{$biAS->id}/receber-na-area")->assertOk();
        $this->assertSame(RequisicaoStatus::EmProcessamento, $requisicao->refresh()->status);

        // --- Área AS: ensaio, executor não valida, 2 validadores -----------
        $this->actingAs($tecAS)->postJson("/api/lab/boletins-internos/{$biAS->id}/iniciar-ensaio")->assertOk();
        $this->biComResultados($biAS, $tecAS, $exameAS, ['reaccao' => 'negativo']);
        $this->assertSame(BoletimInternoStatus::ResultadoRegistado, $biAS->refresh()->status);

        $this->actingAs($tecAS)->postJson("/api/lab/boletins-internos/{$biAS->id}/validar")
            ->assertStatus(422)->assertJsonValidationErrors('validador'); // executor não valida o seu ensaio
        $this->actingAs($qualidade)->postJson("/api/lab/boletins-internos/{$biAS->id}/validar")
            ->assertOk()->assertJsonPath('status', BoletimInternoStatus::EmValidacao->value);
        $this->actingAs($qualidade)->postJson("/api/lab/boletins-internos/{$biAS->id}/validar")->assertStatus(422); // validadores distintos
        $this->actingAs($director)->postJson("/api/lab/boletins-internos/{$biAS->id}/validar")
            ->assertOk()->assertJsonPath('status', BoletimInternoStatus::Validado->value); // validado, NUNCA emitido
        $this->assertNull(BoletimAnalise::query()->where('boletim_interno_id', $biAS->id)->first());
        $this->actingAs($tecAS)->postJson("/api/lab/boletins-internos/{$biAS->id}/devolver")
            ->assertOk()->assertJsonPath('status', BoletimInternoStatus::Devolvido->value);

        // --- Área AB: 3 validadores nomeados ------------------------------
        $this->actingAs($tecAB)->postJson("/api/lab/boletins-internos/{$biAB->id}/iniciar-ensaio")->assertOk();
        $this->biComResultados($biAB->refresh(), $tecAB, $exameAB, ['crescimento' => 'sem_crescimento']);

        $this->actingAs($foraDaLista)->postJson("/api/lab/boletins-internos/{$biAB->id}/validar")->assertStatus(422); // fora da lista nomeada
        $this->actingAs($tecAB)->postJson("/api/lab/boletins-internos/{$biAB->id}/validar")->assertStatus(422); // executor
        $this->actingAs($nomeadoTecnico)->postJson("/api/lab/boletins-internos/{$biAB->id}/validar")->assertOk();
        $this->actingAs($director)->postJson("/api/lab/boletins-internos/{$biAB->id}/validar")
            ->assertOk()->assertJsonPath('status', BoletimInternoStatus::EmValidacao->value); // 2/3 ainda não chega
        $this->actingAs($qualidade)->postJson("/api/lab/boletins-internos/{$biAB->id}/validar")
            ->assertOk()->assertJsonPath('status', BoletimInternoStatus::Validado->value);
        $this->assertCount(3, $biAB->refresh()->validacoes);
        $this->actingAs($tecAB)->postJson("/api/lab/boletins-internos/{$biAB->id}/devolver")->assertOk();

        // --- Fase 3: CGISC recebe a devolução e emite os BA ----------------
        $this->actingAs($r1)->postJson("/api/lab/boletins-internos/{$biAB->id}/emitir")->assertStatus(422); // ainda não recebido na CGISC
        $this->actingAs($r1)->postJson("/api/lab/boletins-internos/{$biAB->id}/receber-devolucao")
            ->assertOk()->assertJsonPath('status', BoletimInternoStatus::RecebidoCgisc->value);
        $this->actingAs($r2)->postJson("/api/lab/boletins-internos/{$biAS->id}/receber-devolucao")->assertOk();
        $this->assertSame(RequisicaoStatus::AguardaEmissao, $requisicao->refresh()->status);

        $this->actingAs($tecAB)->postJson("/api/lab/boletins-internos/{$biAB->id}/emitir")->assertForbidden(); // Técnico nunca emite
        $this->actingAs($r1)->postJson("/api/lab/requisicoes/{$requisicao->id}/encerrar")->assertStatus(422); // ainda há BI por emitir

        $baAB = $this->actingAs($r1)->postJson("/api/lab/boletins-internos/{$biAB->id}/emitir")->assertCreated();
        $this->assertMatchesRegularExpression('/^BA-\d{4}-\d{4}$/', $baAB->json('numero'));
        $this->assertSame("{$requisicao->pat_numero}/AB", $baAB->json('conteudo.boletimInterno'));
        $this->actingAs($r1)->postJson("/api/lab/boletins-internos/{$biAS->id}/emitir")->assertCreated();
        $this->assertSame(2, BoletimAnalise::query()->where('requisicao_id', $requisicao->id)->whereNotNull('emitido_em')->count());
        $this->assertSame(RequisicaoStatus::Emitida, $requisicao->refresh()->status);

        $this->actingAs($r1)->postJson("/api/lab/boletins-analises/{$baAB->json('id')}/entregar", [
            'entregueA' => 'Fazenda Boa Esperança', 'canal' => 'presencial',
        ])->assertOk()->assertJsonPath('canalEntrega', 'presencial');

        $this->actingAs($r1)->postJson("/api/lab/requisicoes/{$requisicao->id}/encerrar")
            ->assertOk()->assertJsonPath('status', RequisicaoStatus::Encerrada->value);
        $this->assertSame(AmostraStatus::Concluida, $a1->refresh()->status);

        // --- Rastreabilidade -------------------------------------------------
        $accoes = AmostraMovimento::query()->where('amostra_id', $a1->id)->pluck('accao')->unique()->values()->all();
        foreach (['recebida', 'conferida', 'aceite', 'encaminhada', 'recebida_area', 'ensaio_iniciado', 'resultado', 'validada', 'devolvida', 'recebida_cgisc', 'emitida', 'entregue'] as $accao) {
            $this->assertContains($accao, $accoes, "Movimento '{$accao}' em falta na rastreabilidade.");
        }
        $this->actingAs($r1)->getJson("/api/lab/amostras/{$a1->id}/movimentos")->assertOk();
        $this->actingAs($director)->getJson("/api/lab/processos/{$requisicao->id}")
            ->assertOk()->assertJsonPath('requisicao.status', RequisicaoStatus::Encerrada->value);
        $this->assertTrue(Amostra::query()->whereKey($a2->id)->first()->status === AmostraStatus::Rejeitada);
    }
}

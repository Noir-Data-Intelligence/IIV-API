<?php

namespace Tests\Feature;

use App\Enums\AmostraStatus;
use App\Enums\AppRole;
use App\Enums\RequisicaoStatus;
use App\Models\CriterioRejeicao;
use App\Models\Requisicao;
use App\Services\Lab\NumeracaoService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\LabFixtures;
use Tests\TestCase;

/** Fase 1 — RecepcaoService via API (DEF-001). */
class LabRecepcaoTest extends TestCase
{
    use LabFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_registo_deriva_areas_dos_exames_sem_escolher_laboratorio(): void
    {
        $ab = $this->area('AB');
        $as = $this->area('AS');
        $exAB = $this->exame($ab);
        $exAS = $this->exame($as);

        $response = $this->actingAs($this->comPapel(AppRole::Recepcionista))->postJson('/api/lab/recepcao/requisicoes',
            $this->payloadRequisicao([['exameId' => $exAB->id], ['exameId' => $exAS->id, 'amostraIndices' => [1]]], 2));

        $response->assertCreated()
            ->assertJsonPath('areas', ['AB', 'AS'])
            ->assertJsonPath('laboratorioId', null)
            ->assertJsonPath('status', 'registada')
            ->assertJsonCount(2, 'amostras');
        $this->assertMatchesRegularExpression('/^REQ-\d{4}-\d{4}$/', $response->json('numero'));
        $this->assertDatabaseCount('requisicao_exames', 2);
        $this->assertDatabaseHas('amostra_movimentos', ['accao' => 'recebida', 'para_local' => 'CGISC']);
    }

    public function test_registo_exige_pelo_menos_um_exame(): void
    {
        $this->actingAs($this->comPapel(AppRole::Recepcionista))
            ->postJson('/api/lab/recepcao/requisicoes', $this->payloadRequisicao([]))
            ->assertStatus(422)->assertJsonValidationErrors('exames');
    }

    public function test_tecnico_nao_pode_registar_na_recepcao(): void
    {
        $exame = $this->exame($this->area('AB'));

        $this->actingAs($this->comPapel(AppRole::Tecnico))
            ->postJson('/api/lab/recepcao/requisicoes', $this->payloadRequisicao([['exameId' => $exame->id]]))
            ->assertForbidden();
    }

    public function test_proveniencia_nao_local_exige_termo_de_entrega(): void
    {
        $exame = $this->exame($this->area('AB'));
        $user = $this->comPapel(AppRole::Recepcionista);

        $this->actingAs($user)->postJson('/api/lab/recepcao/requisicoes',
            $this->payloadRequisicao([['exameId' => $exame->id]], 1, ['provenienciaLocal' => false]))
            ->assertStatus(422)->assertJsonValidationErrors('termoEntregaAssinadoPor');

        $response = $this->actingAs($user)->postJson('/api/lab/recepcao/requisicoes',
            $this->payloadRequisicao([['exameId' => $exame->id]], 1, ['provenienciaLocal' => false, 'termoEntregaAssinadoPor' => 'Motorista X']));
        $response->assertCreated();
        $this->assertMatchesRegularExpression('/^TE-\d{4}-\d{4}$/', $response->json('termoEntregaNumero'));
    }

    public function test_exame_incompativel_com_o_tipo_de_amostra_devolve_422(): void
    {
        $exame = $this->exame($this->area('AR'));
        $exame->update(['tipos_amostra' => ['tecido']]);

        $this->actingAs($this->comPapel(AppRole::Recepcionista))->postJson('/api/lab/recepcao/requisicoes',
            $this->payloadRequisicao([['exameId' => $exame->id, 'amostraIndices' => [0]]]))
            ->assertStatus(422);
    }

    public function test_segunda_conferencia_tem_de_ser_de_tecnico_diferente(): void
    {
        $exame = $this->exame($this->area('AB'));
        $r1 = $this->comPapel(AppRole::Recepcionista);
        $r2 = $this->comPapel(AppRole::Recepcionista);
        $requisicao = $this->registar($r1, [['exameId' => $exame->id]]);
        $amostra = $requisicao->amostras->first();

        $this->conferir($r1, $amostra)->assertCreated()->assertJsonPath('ordem', 1);
        $this->assertSame(AmostraStatus::EmConferencia, $amostra->refresh()->status);
        $this->assertSame(RequisicaoStatus::EmTriagem, $requisicao->refresh()->status);

        $this->conferir($r1, $amostra)->assertStatus(422)->assertJsonValidationErrors('conferencia');
        $this->conferir($r2, $amostra)->assertCreated()->assertJsonPath('ordem', 2);
        $this->assertSame(AmostraStatus::Aceite, $amostra->refresh()->status);
        $this->conferir($this->comPapel(AppRole::Recepcionista), $amostra)->assertStatus(422); // não há 3ª
    }

    public function test_conferencia_nao_conforme_permite_aceitar_com_reserva(): void
    {
        $exame = $this->exame($this->area('AB'));
        $r1 = $this->comPapel(AppRole::Recepcionista);
        $r2 = $this->comPapel(AppRole::Recepcionista);
        $amostra = $this->registar($r1, [['exameId' => $exame->id]])->amostras->first();

        $this->conferir($r1, $amostra, false)->assertCreated()->assertJsonPath('conforme', false);
        $this->actingAs($r1)->postJson("/api/lab/recepcao/amostras/{$amostra->id}/aceitar-com-reserva", ['motivo' => 'x'])
            ->assertStatus(422); // ainda só 1 conferência
        $this->conferir($r2, $amostra)->assertCreated();
        $this->assertSame(AmostraStatus::EmConferencia, $amostra->refresh()->status);

        $this->actingAs($r2)->postJson("/api/lab/recepcao/amostras/{$amostra->id}/aceitar-com-reserva", ['motivo' => 'Volume no limite'])
            ->assertOk()->assertJsonPath('status', AmostraStatus::AceiteComReserva->value)->assertJsonPath('reservaMotivo', 'Volume no limite');
    }

    public function test_rejeicao_de_todas_as_amostras_bloqueia_o_processo(): void
    {
        $exame = $this->exame($this->area('AB'));
        $r1 = $this->comPapel(AppRole::Recepcionista);
        $r2 = $this->comPapel(AppRole::Recepcionista);
        $requisicao = $this->registar($r1, [['exameId' => $exame->id]]);
        $amostra = $requisicao->amostras->first();
        $criterio = CriterioRejeicao::factory()->create();

        $this->actingAs($r1)->postJson("/api/lab/recepcao/amostras/{$amostra->id}/rejeitar", [
            'criterioRejeicaoId' => $criterio->id, 'assinaturaResponsavel' => 'a',
        ])->assertStatus(422)->assertJsonValidationErrors(['segundoResponsavelId', 'assinaturaSegundo']);

        $this->actingAs($r1)->postJson("/api/lab/recepcao/amostras/{$amostra->id}/rejeitar", [
            'criterioRejeicaoId' => $criterio->id, 'assinaturaResponsavel' => 'a',
            'segundoResponsavelId' => $r2->id, 'assinaturaSegundo' => 'b',
        ])->assertOk()->assertJsonPath('status', 'rejeitada')
            ->assertJsonPath('rejeicao.fase', 'recepcao')
            ->assertJsonPath('rejeicao.segundoResponsavelId', $r2->id);

        $this->assertNotNull($amostra->rejeicao()->first()->origem_notificada_em);
        $this->assertSame(RequisicaoStatus::Rejeitada, $requisicao->refresh()->status);
        $this->actingAs($r1)->postJson("/api/lab/recepcao/requisicoes/{$requisicao->id}/abrir-processo")->assertStatus(422);
    }

    public function test_abrir_processo_exige_triagem_concluida(): void
    {
        $exame = $this->exame($this->area('AB'));
        $r1 = $this->comPapel(AppRole::Recepcionista);
        $requisicao = $this->registar($r1, [['exameId' => $exame->id]], 2);
        $this->conferir($r1, $requisicao->amostras[0])->assertCreated();

        $this->actingAs($r1)->postJson("/api/lab/recepcao/requisicoes/{$requisicao->id}/abrir-processo")->assertStatus(422);
    }

    public function test_abrir_processo_gera_um_bi_por_area_e_pats_sem_duplicados(): void
    {
        $ab = $this->area('AB');
        $as = $this->area('AS');
        $exames = [$this->exame($ab), $this->exame($ab), $this->exame($as)]; // 2 exames de AB -> continua 1 BI de AB

        $p1 = $this->processoAberto($exames, 2);
        $p2 = $this->processoAberto([$exames[0]]);

        $this->assertCount(2, $p1->boletinsInternos);
        $this->assertSame(2, $this->biDe($p1, $ab)->amostras()->count());
        $this->assertNotSame($p1->pat_numero, $p2->pat_numero);
        $ano = now()->year;
        $this->assertSame("PAT-001-{$ano}", $p1->pat_numero);
        $this->assertSame("PAT-002-{$ano}", $p2->pat_numero);
        $this->assertSame("PAT-001-{$ano}/AB", $this->biDe($p1, $ab)->numero);
    }

    public function test_numeracao_pat_continua_acima_de_pats_antigos(): void
    {
        $ano = now()->year;
        Requisicao::factory()->create(['numero' => "PAT-041-{$ano}"]); // série antiga (count()+1)

        $this->assertSame("PAT-042-{$ano}", app(NumeracaoService::class)->pat());
        $this->assertSame("PAT-043-{$ano}", app(NumeracaoService::class)->pat());
    }

    public function test_etiquetas_devolvem_codigos_da_amostra_e_do_bi(): void
    {
        $requisicao = $this->processoAberto([$this->exame($this->area('AB'))]);

        $this->actingAs($this->comPapel(AppRole::Recepcionista))
            ->getJson("/api/lab/recepcao/requisicoes/{$requisicao->id}/etiquetas")
            ->assertOk()
            ->assertJsonPath('pat', $requisicao->pat_numero)
            ->assertJsonPath('amostras.0.codigoBarras', "{$requisicao->pat_numero}-A1")
            ->assertJsonPath('boletins.0.codigoBarras', "{$requisicao->pat_numero}/AB");
    }

    public function test_listagem_filtra_por_estado(): void
    {
        $exame = $this->exame($this->area('AB'));
        $user = $this->comPapel(AppRole::Recepcionista);
        $this->registar($user, [['exameId' => $exame->id]]);
        $this->processoAberto([$exame]);

        $this->actingAs($user)->getJson('/api/lab/recepcao/requisicoes?status=registada')
            ->assertOk()->assertJsonCount(1, 'data');
        $this->actingAs($user)->getJson('/api/lab/recepcao/requisicoes?status=processo_aberto,em_processamento')
            ->assertOk()->assertJsonCount(1, 'data');
    }
}

<?php

namespace Tests\Feature;

use App\Enums\AppRole;
use App\Enums\BoletimInternoStatus;
use App\Models\BoletimInterno;
use App\Models\ExameCatalogo;
use App\Models\Notification;
use App\Models\Requisicao;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\LabFixtures;
use Tests\TestCase;

/** Fase 3 — EmissaoService via API (DEF-003). */
class LabEmissaoTest extends TestCase
{
    use LabFixtures, RefreshDatabase;

    private User $cgisc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->cgisc = $this->comPapel(AppRole::Recepcionista);
    }

    /** BI validado e já recebido de volta na CGISC. */
    private function biRecebidoNaCgisc(array $extraRequisicao = [], bool $exigePagamento = false): BoletimInterno
    {
        $as = $this->area('AS', 2);
        $as->update(['exige_pagamento_para_entrega' => $exigePagamento]);
        $tec = $this->membroDe($as);
        $exame = $this->exame($as);
        $requisicao = $this->processoAberto([$exame], 1, $extraRequisicao);
        $bi = $this->biEmAnalise($this->biDe($requisicao, $as), $tec);
        $this->biComResultados($bi, $tec, $exame);
        $this->actingAs($this->comPapel(AppRole::ResponsavelQualidade))->postJson("/api/lab/boletins-internos/{$bi->id}/validar")->assertOk();
        $this->actingAs($this->comPapel(AppRole::DirectorLaboratorio))->postJson("/api/lab/boletins-internos/{$bi->id}/validar")->assertOk();
        $this->actingAs($tec)->postJson("/api/lab/boletins-internos/{$bi->id}/devolver")->assertOk();
        $this->actingAs($this->cgisc)->postJson("/api/lab/boletins-internos/{$bi->id}/receber-devolucao")->assertOk();

        return $bi->refresh();
    }

    public function test_receber_devolucao_exige_bi_devolvido(): void
    {
        $as = $this->area('AS');
        $requisicao = $this->processoAberto([$this->exame($as)]);
        $bi = $this->biDe($requisicao, $as);

        $this->actingAs($this->cgisc)->postJson("/api/lab/boletins-internos/{$bi->id}/receber-devolucao")->assertStatus(422);
    }

    public function test_emissao_gera_ba_com_snapshot_e_assinatura(): void
    {
        $bi = $this->biRecebidoNaCgisc();

        $response = $this->actingAs($this->cgisc)->postJson("/api/lab/boletins-internos/{$bi->id}/emitir");

        $response->assertCreated()
            ->assertJsonPath('emitidoPorId', $this->cgisc->id)
            ->assertJsonPath('boletimInternoId', $bi->id)
            ->assertJsonPath('conteudo.area.codigo', 'AS')
            ->assertJsonCount(2, 'conteudo.validacoes');
        $this->assertNotNull($response->json('assinaturaHash'));
        $this->assertSame(BoletimInternoStatus::Emitido, $bi->refresh()->status);
        $this->actingAs($this->cgisc)->postJson("/api/lab/boletins-internos/{$bi->id}/emitir")->assertStatus(422); // não emite 2x
    }

    public function test_tecnico_responsavel_qualidade_e_director_nao_emitem(): void
    {
        $bi = $this->biRecebidoNaCgisc();

        foreach ([AppRole::Tecnico, AppRole::ResponsavelQualidade, AppRole::DirectorLaboratorio] as $papel) {
            $this->actingAs($this->comPapel($papel))->postJson("/api/lab/boletins-internos/{$bi->id}/emitir")->assertForbidden();
        }
    }

    public function test_entrega_bloqueada_se_area_exige_pagamento(): void
    {
        $bi = $this->biRecebidoNaCgisc(exigePagamento: true);
        $baId = $this->actingAs($this->cgisc)->postJson("/api/lab/boletins-internos/{$bi->id}/emitir")->json('id');

        $this->actingAs($this->cgisc)->postJson("/api/lab/boletins-analises/{$baId}/entregar", ['entregueA' => 'Cliente', 'canal' => 'presencial'])
            ->assertStatus(422)->assertJsonValidationErrors('pagamentoConfirmado');
        $this->actingAs($this->cgisc)->postJson("/api/lab/boletins-analises/{$baId}/entregar", ['entregueA' => 'Cliente', 'canal' => 'presencial', 'pagamentoConfirmado' => true])
            ->assertOk()->assertJsonPath('entregueA', 'Cliente');
        $this->actingAs($this->cgisc)->postJson("/api/lab/boletins-analises/{$baId}/entregar", ['entregueA' => 'Cliente', 'canal' => 'presencial', 'pagamentoConfirmado' => true])
            ->assertStatus(422); // já entregue
    }

    public function test_entrega_nao_bloqueada_por_omissao(): void
    {
        $bi = $this->biRecebidoNaCgisc();
        $baId = $this->actingAs($this->cgisc)->postJson("/api/lab/boletins-internos/{$bi->id}/emitir")->json('id');

        $this->actingAs($this->cgisc)->postJson("/api/lab/boletins-analises/{$baId}/entregar", ['entregueA' => 'Cliente', 'canal' => 'email'])
            ->assertOk();
    }

    public function test_origem_publica_notifica_o_isv_automaticamente(): void
    {
        $isv = $this->comPapel(AppRole::Isv);
        $bi = $this->biRecebidoNaCgisc(['origemTipo' => 'publica']);

        $response = $this->actingAs($this->cgisc)->postJson("/api/lab/boletins-internos/{$bi->id}/emitir")->assertCreated();

        $this->assertNotNull($response->json('isvNotificadoEm'));
        $this->assertSame(1, Notification::query()->where('user_id', $isv->id)->count());
    }

    public function test_encerrar_processo_so_com_todos_os_bi_terminados(): void
    {
        $bi = $this->biRecebidoNaCgisc();
        $requisicao = Requisicao::query()->findOrFail($bi->requisicao_id);

        $this->actingAs($this->cgisc)->postJson("/api/lab/requisicoes/{$requisicao->id}/encerrar")->assertStatus(422);
        $this->actingAs($this->cgisc)->postJson("/api/lab/boletins-internos/{$bi->id}/emitir")->assertCreated();

        $this->actingAs($this->cgisc)->getJson('/api/lab/emissao')->assertOk()->assertJsonCount(1, 'porEncerrar')->assertJsonCount(1, 'emitidos');
        $this->actingAs($this->cgisc)->postJson("/api/lab/requisicoes/{$requisicao->id}/encerrar")
            ->assertOk()->assertJsonPath('status', 'encerrada');
        $this->assertNotNull($requisicao->refresh()->encerrada_em);
    }

    public function test_fila_de_emissao_vedada_a_tecnicos(): void
    {
        $this->area('AS');
        $this->actingAs($this->comPapel(AppRole::Tecnico))->getJson('/api/lab/emissao')->assertForbidden();
        $this->actingAs($this->comPapel(AppRole::Tecnico))->getJson('/api/lab/recepcao/requisicoes')->assertForbidden();
        $this->actingAs($this->comPapel(AppRole::Recepcionista))->getJson('/api/lab/areas/AS/fila')->assertForbidden();
    }

    public function test_catalogo_de_exames_crud_so_para_director(): void
    {
        $as = $this->area('AS');
        $payload = [
            'laboratorioId' => $as->id, 'codigo' => 'AS-NOVO', 'nome' => 'Novo exame',
            'tiposAmostra' => ['soro'], 'camposResultado' => [['chave' => 'titulo', 'tipo' => 'numero']],
        ];

        $this->actingAs($this->comPapel(AppRole::Tecnico))->postJson('/api/lab/exames', $payload)->assertForbidden();
        $id = $this->actingAs($this->comPapel(AppRole::DirectorLaboratorio))->postJson('/api/lab/exames', $payload)
            ->assertCreated()->json('id');

        $this->actingAs($this->cgisc)->getJson('/api/lab/exames?activos=1')->assertOk()->assertJsonPath('0.codigo', 'AS-NOVO');

        // Exame já pedido numa requisição é desactivado em vez de apagado.
        $this->registar($this->cgisc, [['exameId' => $id]]);
        $this->actingAs($this->comPapel(AppRole::DirectorLaboratorio))->deleteJson("/api/lab/exames/{$id}")
            ->assertOk()->assertJsonPath('activo', false);
        $this->assertNotNull(ExameCatalogo::query()->find($id));
    }
}

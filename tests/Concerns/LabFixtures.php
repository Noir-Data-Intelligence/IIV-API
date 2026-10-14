<?php

namespace Tests\Concerns;

use App\Enums\AppRole;
use App\Models\Amostra;
use App\Models\BoletimInterno;
use App\Models\ExameCatalogo;
use App\Models\Laboratorio;
use App\Models\Requisicao;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Support\Str;

/**
 * Helpers partilhados pelos testes do Laboratório por fase. Exercitam
 * sempre a API (rotas + RBAC + policy + serviço), nunca escrevem `status`
 * directamente.
 */
trait LabFixtures
{
    protected function comPapel(AppRole $role): User
    {
        $user = User::factory()->create();
        UserRole::query()->create(['id' => (string) Str::uuid(), 'user_id' => $user->id, 'role' => $role]);

        return $user;
    }

    protected function membroDe(Laboratorio $laboratorio, ?User $user = null): User
    {
        $user ??= $this->comPapel(AppRole::Tecnico);
        $laboratorio->tecnicos()->syncWithoutDetaching([$user->id => ['funcao' => 'Técnico']]);

        return $user;
    }

    protected function area(string $code, int $validadores = 2, ?array $nomeados = null): Laboratorio
    {
        return Laboratorio::factory()->create([
            'code' => $code,
            'validador_count' => $validadores,
            'validadores_nomeados' => $nomeados,
        ]);
    }

    protected function exame(Laboratorio $laboratorio, array $campos = [['chave' => 'valor', 'tipo' => 'numero']]): ExameCatalogo
    {
        return ExameCatalogo::factory()->create([
            'laboratorio_id' => $laboratorio->id,
            'campos_resultado' => $campos,
        ]);
    }

    /** @return array<string, mixed> */
    protected function payloadRequisicao(array $exames, int $nAmostras = 1, array $extra = []): array
    {
        return [
            'tipoSujeito' => 'animal',
            'clienteNome' => 'Fazenda Boa Esperança',
            'consentimento' => true,
            'assinaturaCliente' => 'assinatura-cliente',
            'origemTipo' => 'privada',
            'amostras' => array_fill(0, $nAmostras, ['tipoAmostra' => 'sangue']),
            'exames' => $exames,
            ...$extra,
        ];
    }

    protected function registar(User $recepcionista, array $exames, int $nAmostras = 1, array $extra = []): Requisicao
    {
        $response = $this->actingAs($recepcionista)
            ->postJson('/api/lab/recepcao/requisicoes', $this->payloadRequisicao($exames, $nAmostras, $extra));
        $response->assertCreated();

        return Requisicao::query()->findOrFail($response->json('id'));
    }

    /** @return array<string, bool> */
    protected function criterios(bool $conforme = true): array
    {
        return [
            'identificacao' => true, 'quantidade' => true, 'conservacao' => $conforme,
            'macroscopica' => true, 'caracteristicas_fisicas' => true, 'tempo_colheita' => true,
        ];
    }

    protected function conferir(User $user, Amostra $amostra, bool $conforme = true)
    {
        $criterios = [];
        foreach ($this->criterios($conforme) as $k => $v) {
            $criterios["crit_{$k}"] = $v;
        }

        return $this->actingAs($user)->postJson("/api/lab/recepcao/amostras/{$amostra->id}/conferencias", ['criterios' => $criterios]);
    }

    /** Dupla conferência conforme (2 recepcionistas distintos). */
    protected function aceitar(Amostra $amostra, User $r1, User $r2): void
    {
        $this->conferir($r1, $amostra)->assertCreated();
        $this->conferir($r2, $amostra)->assertCreated();
    }

    /**
     * Requisição com N amostras, todas aceites, e processo aberto — 1 exame
     * por área indicada.
     *
     * @param  list<ExameCatalogo>  $exames
     */
    protected function processoAberto(array $exames, int $nAmostras = 1, array $extra = []): Requisicao
    {
        $r1 = $this->comPapel(AppRole::Recepcionista);
        $r2 = $this->comPapel(AppRole::Recepcionista);
        $requisicao = $this->registar($r1, array_map(fn (ExameCatalogo $e) => ['exameId' => $e->id], $exames), $nAmostras, $extra);
        foreach ($requisicao->amostras as $amostra) {
            $this->aceitar($amostra, $r1, $r2);
        }
        $this->actingAs($r1)->postJson("/api/lab/recepcao/requisicoes/{$requisicao->id}/abrir-processo")->assertOk();

        return $requisicao->refresh();
    }

    /** Leva o BI de `gerado` até `em_analise` (encaminhado e recebido pelo membro da área). */
    protected function biEmAnalise(BoletimInterno $bi, User $membro): BoletimInterno
    {
        $this->actingAs($this->comPapel(AppRole::Recepcionista))->postJson("/api/lab/boletins-internos/{$bi->id}/encaminhar")->assertOk();
        $this->actingAs($membro)->postJson("/api/lab/boletins-internos/{$bi->id}/receber-na-area")->assertOk();
        $this->actingAs($membro)->postJson("/api/lab/boletins-internos/{$bi->id}/iniciar-ensaio")->assertOk();

        return $bi->refresh();
    }

    /** Regista o ensaio de todas as amostras do BI para o exame dado e submete para validação. */
    protected function biComResultados(BoletimInterno $bi, User $executor, ExameCatalogo $exame, array $resultado = ['valor' => 1], string $qualitativo = 'negativo'): BoletimInterno
    {
        foreach ($bi->amostras as $amostra) {
            $this->actingAs($executor)->putJson("/api/lab/boletins-internos/{$bi->id}/ensaios", [
                'amostraId' => $amostra->id,
                'exameId' => $exame->id,
                'equipamentoId' => 'EQ-01',
                'loteReagente' => 'LR-2026-01',
                'resultado' => $resultado,
                'resultadoQualitativo' => $qualitativo,
            ])->assertSuccessful();
        }
        $this->actingAs($executor)->postJson("/api/lab/boletins-internos/{$bi->id}/submeter-validacao")->assertOk();

        return $bi->refresh();
    }

    protected function biDe(Requisicao $requisicao, Laboratorio $laboratorio): BoletimInterno
    {
        return BoletimInterno::query()
            ->where('requisicao_id', $requisicao->id)
            ->where('laboratorio_id', $laboratorio->id)
            ->firstOrFail();
    }
}

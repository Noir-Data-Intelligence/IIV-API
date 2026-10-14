<?php

namespace App\Services\Lab;

use App\Enums\AmostraStatus;
use App\Enums\BoletimInternoStatus;
use App\Enums\RequisicaoStatus;
use App\Models\Amostra;
use App\Models\AmostraConferencia;
use App\Models\AmostraRejeicao;
use App\Models\BoletimInterno;
use App\Models\CriterioRejeicao;
use App\Models\ExameCatalogo;
use App\Models\Laboratorio;
use App\Models\Requisicao;
use App\Models\RequisicaoExame;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Fase 1 — Recepção (CGISC), resolve DEF-001. A CGISC é o único ponto de
 * entrada: regista a requisição + amostras + exames pedidos, faz a dupla
 * conferência de conformidade (6 critérios, 2 técnicos diferentes), rejeita
 * com Termo de Rejeição (2 assinaturas) e, para as amostras aceites, abre o
 * processo: atribui PAT e gera 1 BI por área DERIVADA dos exames pedidos.
 */
class RecepcaoService
{
    public function __construct(
        private readonly NumeracaoService $numeracao,
        private readonly LabSupport $support,
    ) {}

    /**
     * @param  array<string, mixed>  $data  payload validado (camelCase, ver RegistarRequisicaoRequest)
     */
    public function registarRequisicao(array $data, User $user): Requisicao
    {
        $provenienciaLocal = (bool) ($data['provenienciaLocal'] ?? true);
        if (! $provenienciaLocal && empty($data['termoEntregaAssinadoPor'])) {
            throw ValidationException::withMessages([
                'termoEntregaAssinadoPor' => ['Amostras de proveniência não local exigem Termo de Entrega assinado.'],
            ]);
        }

        $examesPedidos = $data['exames'] ?? [];
        $catalogo = ExameCatalogo::query()->whereIn('id', array_column($examesPedidos, 'exameId'))->get()->keyBy('id');
        foreach ($examesPedidos as $i => $pedido) {
            $exame = $catalogo->get($pedido['exameId']);
            if (! $exame || ! $exame->activo) {
                throw ValidationException::withMessages(["exames.{$i}.exameId" => ['Exame inexistente ou inactivo no catálogo.']]);
            }
            foreach ($pedido['amostraIndices'] ?? [] as $indice) {
                $amostraData = $data['amostras'][$indice] ?? null;
                if ($amostraData === null) {
                    throw ValidationException::withMessages(["exames.{$i}.amostraIndices" => ["Amostra #{$indice} inexistente na requisição."]]);
                }
                if (! empty($exame->tipos_amostra) && ! in_array($amostraData['tipoAmostra'], $exame->tipos_amostra, true)) {
                    throw ValidationException::withMessages([
                        "exames.{$i}.amostraIndices" => ["O exame {$exame->codigo} não se aplica a amostras do tipo \"{$amostraData['tipoAmostra']}\"."],
                    ]);
                }
            }
        }

        return DB::transaction(function () use ($data, $user, $provenienciaLocal, $examesPedidos, $catalogo) {
            $requisicao = Requisicao::query()->create([
                'id' => (string) Str::uuid(),
                'numero' => $this->numeracao->requisicao(),
                // @deprecated — só preenchido por chamadores antigos (POST /requisicoes).
                'laboratorio_id' => $data['laboratorioId'] ?? null,
                'tipo_sujeito' => $data['tipoSujeito'],
                'cliente_nome' => $data['clienteNome'],
                'cliente_contacto' => $data['clienteContacto'] ?? null,
                'veterinario_responsavel' => $data['veterinarioResponsavel'] ?? null,
                'dados_epidemiologicos' => $data['dadosEpidemiologicos'] ?? null,
                'dados_facturacao' => $data['dadosFacturacao'] ?? null,
                'consentimento' => (bool) $data['consentimento'],
                'assinatura_cliente' => $data['assinaturaCliente'],
                'created_by' => $user->id,
                'status' => RequisicaoStatus::Registada,
                'origem_tipo' => $data['origemTipo'] ?? null,
                'proveniencia_local' => $provenienciaLocal,
                'termo_entrega_numero' => $provenienciaLocal ? null : ($data['termoEntregaNumero'] ?? $this->numeracao->termoEntrega()),
                'termo_entrega_assinado_por' => $provenienciaLocal ? null : $data['termoEntregaAssinadoPor'],
                'entregue_por' => $data['entreguePor'] ?? null,
                'entregue_em' => now(),
                'recebida_por_id' => $user->id,
                'conservacao' => $data['conservacao'] ?? null,
                'colheita_em' => $data['colheitaEm'] ?? null,
                'doencas_suspeitas' => $data['doencasSuspeitas'] ?? null,
            ]);

            $amostras = [];
            foreach ($data['amostras'] as $indice => $amostraData) {
                $amostra = Amostra::query()->create([
                    'id' => (string) Str::uuid(),
                    'requisicao_id' => $requisicao->id,
                    'numero' => "{$requisicao->numero}-A".($indice + 1),
                    'tipo_amostra' => $amostraData['tipoAmostra'],
                    'origem_matriz' => $amostraData['origemMatriz'] ?? null,
                    'ponto_colheita' => $amostraData['pontoColheita'] ?? null,
                    'latitude' => $amostraData['latitude'] ?? null,
                    'longitude' => $amostraData['longitude'] ?? null,
                    'colhida_por' => $amostraData['colhidaPor'] ?? null,
                    'sujeito' => $amostraData['sujeito'] ?? null,
                    'recebida_em' => now(),
                    'status' => AmostraStatus::Recebida,
                    'localizacao_actual' => LabSupport::CGISC,
                ]);
                $amostras[$indice] = $amostra;
                $this->support->movimento($amostra, 'recebida', $user, null, $data['entreguePor'] ?? 'Origem', LabSupport::CGISC);
            }

            foreach ($examesPedidos as $pedido) {
                $exame = $catalogo->get($pedido['exameId']);
                $indices = $pedido['amostraIndices'] ?? null;
                $alvos = $indices === null || $indices === [] ? [null] : array_map(fn ($i) => $amostras[$i]->id, $indices);
                foreach ($alvos as $amostraId) {
                    RequisicaoExame::query()->create([
                        'id' => (string) Str::uuid(),
                        'requisicao_id' => $requisicao->id,
                        'amostra_id' => $amostraId,
                        'exame_id' => $exame->id,
                        'laboratorio_id' => $exame->laboratorio_id,
                    ]);
                }
            }

            return $requisicao->refresh();
        });
    }

    /**
     * Conferência de conformidade. A 1ª passa a amostra a `em_conferencia`;
     * a 2ª TEM de ser feita por um técnico diferente do da 1ª. Se ambas
     * forem conformes a amostra fica `aceite`; caso contrário aguarda decisão
     * (Termo de Rejeição ou aceitação com reserva).
     *
     * @param  array<string, bool>  $criterios  chaves = AmostraConferencia::CRITERIOS
     */
    public function conferir(Amostra $amostra, User $tecnico, array $criterios, ?string $observacao = null): AmostraConferencia
    {
        return DB::transaction(function () use ($amostra, $tecnico, $criterios, $observacao) {
            $amostra = Amostra::query()->lockForUpdate()->findOrFail($amostra->id);
            $anteriores = $amostra->conferencias()->get();
            $ordem = $anteriores->count() + 1;

            if ($ordem > 2) {
                throw ValidationException::withMessages(['conferencia' => ['A amostra já tem as duas conferências registadas.']]);
            }
            $estadoEsperado = $ordem === 1 ? AmostraStatus::Recebida : AmostraStatus::EmConferencia;
            if ($amostra->status !== $estadoEsperado) {
                throw ValidationException::withMessages([
                    'status' => ["A {$ordem}ª conferência exige a amostra em \"{$estadoEsperado->value}\" (está em \"{$amostra->status->value}\")."],
                ]);
            }
            if ($ordem === 2 && $anteriores->first()->tecnico_id === $tecnico->id) {
                throw ValidationException::withMessages([
                    'conferencia' => ['A 2ª conferência tem de ser feita por um técnico diferente do que fez a 1ª.'],
                ]);
            }

            $valores = [];
            foreach (array_keys(AmostraConferencia::CRITERIOS) as $chave) {
                $valores[$chave] = (bool) ($criterios[$chave] ?? false);
            }
            $conforme = ! in_array(false, $valores, true);

            $conferencia = AmostraConferencia::query()->create([
                'id' => (string) Str::uuid(),
                'amostra_id' => $amostra->id,
                'tecnico_id' => $tecnico->id,
                'ordem' => $ordem,
                ...$valores,
                'conforme' => $conforme,
                'observacao' => $observacao,
            ]);

            $this->support->movimento($amostra, 'conferida', $tecnico, null, LabSupport::CGISC, LabSupport::CGISC,
                "{$ordem}ª conferência: ".($conforme ? 'conforme' : 'não conforme').($observacao ? " — {$observacao}" : ''));

            if ($ordem === 1) {
                $this->support->transitarAmostra($amostra, AmostraStatus::EmConferencia);
                $requisicao = $amostra->requisicao;
                if ($requisicao->status === RequisicaoStatus::Registada) {
                    $requisicao->update(['status' => RequisicaoStatus::EmTriagem]);
                }
            } elseif ($conforme && $anteriores->first()->conforme) {
                $this->support->transitarAmostra($amostra, AmostraStatus::Aceite, ['aceite_em' => now()]);
                $this->support->movimento($amostra, 'aceite', $tecnico, null, LabSupport::CGISC, LabSupport::CGISC, 'Dupla conferência conforme.');
            }

            return $conferencia;
        });
    }

    /** Aceita uma amostra não totalmente conforme, com reserva fundamentada (após as 2 conferências). */
    public function aceitarComReserva(Amostra $amostra, User $user, string $motivo): Amostra
    {
        return DB::transaction(function () use ($amostra, $user, $motivo) {
            if ($amostra->status !== AmostraStatus::EmConferencia || $amostra->conferencias()->count() < 2) {
                throw ValidationException::withMessages([
                    'status' => ['A aceitação com reserva exige as duas conferências registadas e a amostra ainda em conferência.'],
                ]);
            }

            $this->support->transitarAmostra($amostra, AmostraStatus::AceiteComReserva, [
                'aceite_com_reserva' => true,
                'reserva_motivo' => $motivo,
                'aceite_em' => now(),
            ]);
            $this->support->movimento($amostra, 'aceite', $user, null, LabSupport::CGISC, LabSupport::CGISC, "Aceite com reserva: {$motivo}");

            return $amostra->refresh();
        });
    }

    /**
     * Termo de Rejeição na recepção: critério + 2 assinaturas de
     * responsáveis DIFERENTES. Notifica a origem (stub: carimba
     * `origem_notificada_em` — não existe ainda canal externo) e bloqueia o
     * processo se todas as amostras ficarem rejeitadas.
     */
    public function rejeitar(
        Amostra $amostra,
        User $responsavel,
        string $assinatura,
        User $segundo,
        string $assinaturaSegundo,
        CriterioRejeicao $criterio,
        ?string $detalhe = null,
        ?string $accaoTomada = null,
    ): AmostraRejeicao {
        if ($segundo->id === $responsavel->id) {
            throw ValidationException::withMessages([
                'segundoResponsavelId' => ['O Termo de Rejeição exige a assinatura de dois responsáveis diferentes.'],
            ]);
        }
        if (! in_array($amostra->status, [AmostraStatus::Recebida, AmostraStatus::EmConferencia], true)) {
            throw ValidationException::withMessages([
                'status' => ['Só é possível rejeitar na recepção uma amostra ainda em triagem (recebida/em conferência).'],
            ]);
        }

        return DB::transaction(function () use ($amostra, $responsavel, $assinatura, $segundo, $assinaturaSegundo, $criterio, $detalhe, $accaoTomada) {
            $rejeicao = AmostraRejeicao::query()->create([
                'id' => (string) Str::uuid(),
                'amostra_id' => $amostra->id,
                'criterio_rejeicao_id' => $criterio->id,
                'responsavel_id' => $responsavel->id,
                'detalhe' => $detalhe,
                'assinatura_responsavel' => $assinatura,
                'rejeitada_em' => now(),
                'fase' => 'recepcao',
                'laboratorio_id' => $criterio->laboratorio_id,
                'segundo_responsavel_id' => $segundo->id,
                'assinatura_segundo' => $assinaturaSegundo,
                // Stub de notificação à origem — a ligar a email/SMS/portal quando existir.
                'origem_notificada_em' => now(),
                'accao_tomada' => $accaoTomada,
            ]);

            $this->support->transitarAmostra($amostra, AmostraStatus::Rejeitada);
            $this->support->movimento($amostra, 'rejeitada', $responsavel, null, LabSupport::CGISC, null,
                "Termo de Rejeição ({$criterio->codigo}): {$criterio->motivo}");

            $requisicao = $amostra->requisicao()->first();
            if ($requisicao->status === RequisicaoStatus::Registada) {
                $requisicao->update(['status' => RequisicaoStatus::EmTriagem]);
            }
            $todasRejeitadas = $requisicao->amostras()->get()->every(fn (Amostra $a) => $a->status === AmostraStatus::Rejeitada);
            if ($todasRejeitadas) {
                $requisicao->update(['status' => RequisicaoStatus::Rejeitada]);
            }

            return $rejeicao;
        });
    }

    /**
     * Abre o processo: atribui o PAT e gera 1 BI por área distinta derivada
     * dos exames pedidos (D.0#1), com as amostras aceites a que cada exame
     * se aplica. Exige triagem concluída de TODAS as amostras e pelo menos
     * uma aceite.
     *
     * @return Collection<int, BoletimInterno>
     */
    public function abrirProcesso(Requisicao $requisicao, User $user): Collection
    {
        return DB::transaction(function () use ($requisicao) {
            $requisicao = Requisicao::query()->lockForUpdate()->findOrFail($requisicao->id);

            if (! $requisicao->status->canTransitionTo(RequisicaoStatus::ProcessoAberto)) {
                throw ValidationException::withMessages([
                    'status' => ["Não é possível abrir o processo de uma requisição em \"{$requisicao->status->value}\"."],
                ]);
            }

            $amostras = $requisicao->amostras()->orderBy('numero')->get();
            $pendentes = $amostras->filter(fn (Amostra $a) => ! $a->status->triagemConcluida());
            if ($pendentes->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'amostras' => ['Triagem incompleta: '.$pendentes->pluck('numero')->implode(', ').' ainda sem decisão (aceite/rejeitada).'],
                ]);
            }
            $aceites = $amostras->filter(fn (Amostra $a) => $a->status->foiAceite())->values();
            if ($aceites->isEmpty()) {
                throw ValidationException::withMessages(['amostras' => ['Nenhuma amostra aceite — o processo não pode ser aberto.']]);
            }

            $pat = $this->numeracao->pat();
            $requisicao->update([
                'pat_numero' => $pat,
                'processo_aberto_em' => now(),
                'status' => RequisicaoStatus::ProcessoAberto,
            ]);

            foreach ($amostras->values() as $i => $amostra) {
                if ($amostra->status->foiAceite()) {
                    $amostra->update(['codigo_barras' => "{$pat}-A".($i + 1)]);
                }
            }

            $pedidos = $requisicao->exames()->with('exame')->get();
            $porArea = $pedidos->groupBy('laboratorio_id');

            // Compatibilidade: requisições antigas sem exames pedidos mas com
            // `laboratorio_id` (POST /requisicoes legado) geram 1 BI dessa área.
            if ($porArea->isEmpty() && $requisicao->laboratorio_id) {
                $porArea = collect([$requisicao->laboratorio_id => collect()]);
            }
            if ($porArea->isEmpty()) {
                throw ValidationException::withMessages(['exames' => ['A requisição não tem exames pedidos — não é possível derivar as áreas de destino.']]);
            }

            $bis = collect();
            foreach ($porArea as $laboratorioId => $pedidosArea) {
                $laboratorio = Laboratorio::query()->findOrFail($laboratorioId);
                $amostrasArea = $pedidosArea->isEmpty()
                    ? $aceites
                    : $aceites->filter(fn (Amostra $a) => $pedidosArea->contains(fn (RequisicaoExame $p) => $p->aplicaA($a->id)))->values();

                if ($amostrasArea->isEmpty()) {
                    continue; // todas as amostras desta área foram rejeitadas
                }

                $slaHoras = $pedidosArea->map(fn (RequisicaoExame $p) => $p->exame?->sla_horas)->filter()->max() ?? $laboratorio->sla_horas;
                $numero = $this->numeroBi($pat, $laboratorio);

                $bi = BoletimInterno::query()->create([
                    'id' => (string) Str::uuid(),
                    'requisicao_id' => $requisicao->id,
                    'laboratorio_id' => $laboratorio->id,
                    'numero' => $numero,
                    'numero_analise' => $numero,
                    'exames' => $pedidosArea->map(fn (RequisicaoExame $p) => $p->exame?->codigo)->filter()->unique()->values()->all(),
                    'entrada_em' => now(),
                    'prazo_em' => $slaHoras ? now()->addHours($slaHoras) : null,
                    'status' => BoletimInternoStatus::Gerado,
                ]);
                $bi->amostras()->attach($amostrasArea->mapWithKeys(fn (Amostra $a) => [$a->id => ['id' => (string) Str::uuid(), 'estado' => 'pendente']])->all());
                RequisicaoExame::query()->whereIn('id', $pedidosArea->pluck('id'))->update(['boletim_interno_id' => $bi->id]);

                $bis->push($bi);
            }

            return $bis;
        });
    }

    /** `PAT-###-ANO/AB`; se a área já tiver BI nesse processo (reencaminhamento), sufixo -2, -3... */
    public function numeroBi(string $pat, Laboratorio $laboratorio): string
    {
        $base = "{$pat}/{$laboratorio->code}";
        $numero = $base;
        $n = 1;
        while (BoletimInterno::query()->where('numero', $numero)->exists()) {
            $n++;
            $numero = "{$base}-{$n}";
        }

        return $numero;
    }

    /**
     * Dados para impressão das etiquetas (amostras aceites e BI).
     * STUB funcional "a melhorar": devolve o texto do código de barras e o
     * payload do QR; o render real (PDF com Code128/QR) fica no frontend ou
     * numa biblioteca a acrescentar (nenhuma disponível no projecto hoje).
     *
     * @return array{pat: ?string, amostras: list<array<string, mixed>>, boletins: list<array<string, mixed>>}
     */
    public function etiquetas(Requisicao $requisicao): array
    {
        $requisicao->load(['amostras.boletinsInternos.laboratorio', 'boletinsInternos.laboratorio']);

        return [
            'pat' => $requisicao->pat_numero,
            'amostras' => $requisicao->amostras
                ->filter(fn (Amostra $a) => $a->codigo_barras !== null)
                ->map(fn (Amostra $a) => [
                    'amostraId' => $a->id,
                    'codigoBarras' => $a->codigo_barras,
                    'numero' => $a->numero,
                    'tipoAmostra' => $a->tipo_amostra,
                    'areas' => $a->boletinsInternos->map(fn (BoletimInterno $b) => $b->laboratorio?->code)->filter()->values()->all(),
                    'qrPayload' => json_encode(['t' => 'amostra', 'c' => $a->codigo_barras, 'pat' => $requisicao->pat_numero]),
                ])->values()->all(),
            'boletins' => $requisicao->boletinsInternos->map(fn (BoletimInterno $b) => [
                'boletimInternoId' => $b->id,
                'codigoBarras' => $b->numero,
                'area' => $b->laboratorio?->code,
                'qrPayload' => json_encode(['t' => 'bi', 'c' => $b->numero]),
            ])->values()->all(),
        ];
    }
}

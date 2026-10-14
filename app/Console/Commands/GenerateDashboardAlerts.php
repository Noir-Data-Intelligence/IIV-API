<?php

namespace App\Console\Commands;

use App\Enums\BoletimInternoStatus;
use App\Models\BoletimInterno;
use App\Models\DashboardAlertHistory;
use App\Models\InseminationRecord;
use App\Models\Insumo;
use App\Models\Laboratorio;
use App\Models\Lote;
use App\Models\NaoConformidade;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Gera e persiste alertas de dashboard de forma independente de qualquer
 * sessão de utilizador (o registo anterior só acontecia quando alguém tinha
 * o Dashboard aberto no browser — ver auditoria funcional, Histórico de
 * Alertas). Cobre deliberadamente só os eventos já citados no documento
 * (lotes a expirar/expirados, stock crítico, não conformidades abertas,
 * partos previstos a aproximar-se) — não é um motor de regras genérico/
 * configurável.
 *
 * Um alerta por REGISTO afectado (não agregado), com `entity_type`/
 * `entity_id` — o "registo associado" pedido pela auditoria — e deduplicado
 * por (metric_key, entity_id) enquanto não for marcado `resolvido`.
 *
 * Agendar via `routes/console.php` (`Schedule::command(...)->daily()`);
 * exige o cron do Laravel Scheduler activo na VPS (`* * * * * php artisan
 * schedule:run`).
 */
class GenerateDashboardAlerts extends Command
{
    protected $signature = 'alerts:generate';

    protected $description = 'Gera alertas de dashboard (lotes a expirar/expirados, stock crítico, não conformidades, partos previstos) independentes de sessão de utilizador.';

    /** Janela (em dias) a partir da qual um parto previsto passa a gerar alerta. */
    private const DIAS_AVISO_PARTO = 7;

    public function handle(): int
    {
        $lotes = $this->generateLoteAlerts();
        $insumos = $this->generateInsumoAlerts();
        $ncs = $this->generateNaoConformidadeAlerts();
        $partos = $this->generateInseminacaoAlerts();
        $slas = $this->generateSlaAlerts();

        $this->info("Alertas gerados: {$lotes} de lotes, {$insumos} de insumos, {$ncs} de não conformidades, {$partos} de partos previstos, {$slas} de quebras de SLA.");

        return self::SUCCESS;
    }

    private function generateLoteAlerts(): int
    {
        $count = 0;
        $hoje = now()->toDateString();
        $limite30dias = now()->addDays(30)->toDateString();

        Lote::query()->whereNotNull('expiry_date')->each(function (Lote $lote) use (&$count, $hoje, $limite30dias) {
            $expiryDate = $lote->expiry_date->toDateString();
            if ($expiryDate < $hoje) {
                $count += $this->registar('batchesExpired', 'Lotes Expirados', 1, 1, 'destructive', 'lote', $lote->id) ? 1 : 0;
            } elseif ($expiryDate <= $limite30dias) {
                $count += $this->registar('expiringSoon', 'Lotes a Expirar', 1, 1, 'warning', 'lote', $lote->id) ? 1 : 0;
            }
        });

        return $count;
    }

    private function generateInsumoAlerts(): int
    {
        $count = 0;

        Insumo::query()->get()->each(function (Insumo $insumo) use (&$count) {
            $quantity = $insumo->quantity ?? 0;
            $minStock = $insumo->min_stock ?? 0;
            if ($quantity <= $minStock) {
                $count += $this->registar('lowStock', 'Stock Crítico', $quantity, $minStock, 'destructive', 'insumo', $insumo->id) ? 1 : 0;
            }
        });

        return $count;
    }

    private function generateNaoConformidadeAlerts(): int
    {
        $count = 0;

        NaoConformidade::query()->whereIn('status', ['aberta', 'em_resolucao'])->each(function (NaoConformidade $nc) use (&$count) {
            $count += $this->registar('ncOpen', 'Não Conformidades', 1, 1, 'destructive', 'nao_conformidade', $nc->id) ? 1 : 0;
        });

        return $count;
    }

    /**
     * Inseminações cujo parto previsto ocorre nos próximos
     * `DIAS_AVISO_PARTO` dias (auditoria funcional, secção 16 — "gerar alerta
     * quando a data prevista do parto se aproximar").
     *
     * Exclui deliberadamente os registos com resultado `falhou`: sem gestação
     * não há parto a acontecer, mesmo que a data prevista tenha ficado
     * gravada no registo.
     */
    private function generateInseminacaoAlerts(): int
    {
        $count = 0;
        $hoje = now()->toDateString();
        $limite = now()->addDays(self::DIAS_AVISO_PARTO)->toDateString();

        InseminationRecord::query()
            ->whereNotNull('expected_birth_date')
            ->where('result', '!=', 'falhou')
            ->each(function (InseminationRecord $registo) use (&$count, $hoje, $limite) {
                $parto = $registo->expected_birth_date->toDateString();
                if ($parto >= $hoje && $parto <= $limite) {
                    $count += $this->registar('birthsUpcoming', 'Partos Previstos', 1, 1, 'warning', 'inseminacao', $registo->id) ? 1 : 0;
                }
            });

        return $count;
    }

    /**
     * Pedido do cliente: "total autonomia de configuração de alertas (SLA)
     * para cada componente de forma independente" — cada laboratório
     * (`Laboratorio.sla_rule_type`/`sla_active`) define o seu próprio prazo,
     * em vez de um limiar global único. Só considera boletins ainda em
     * curso (não `Aprovado`/`Comunicado`) com `entrada_em` registado.
     *
     * `horas_desde_entrada_diferenciado` (ex. Bacteriologia: 24h negativo /
     * 72h positivo) não tem, hoje, um campo estruturado que classifique o
     * resultado como positivo/negativo antes da validação — usa-se sempre o
     * prazo mais curto (`sla_horas_negativo`) como cautela, nunca alertando
     * tarde demais.
     */
    private function generateSlaAlerts(): int
    {
        $count = 0;

        Laboratorio::query()
            ->where('sla_active', true)
            ->whereNotNull('sla_rule_type')
            ->each(function (Laboratorio $laboratorio) use (&$count) {
                $prazoEmHoras = match ($laboratorio->sla_rule_type) {
                    'horas_desde_entrada' => $laboratorio->sla_horas,
                    'horas_desde_entrada_diferenciado' => $laboratorio->sla_horas_negativo ?? $laboratorio->sla_horas,
                    'dias_uteis' => $laboratorio->sla_dias ? $laboratorio->sla_dias * 24 : null,
                    default => null,
                };

                if (! $prazoEmHoras) {
                    return;
                }

                BoletimInterno::query()
                    ->whereNotNull('entrada_em')
                    // SLA corre até à emissão do Boletim de Análises (ou rejeição na área).
                    ->whereNotIn('status', [BoletimInternoStatus::Emitido->value, BoletimInternoStatus::RejeitadoNaArea->value])
                    ->where(fn ($q) => $q->where('laboratorio_id', $laboratorio->id)
                        // BI antigos (1 por amostra) sem laboratorio_id próprio.
                        ->orWhere(fn ($legacy) => $legacy->whereNull('laboratorio_id')
                            ->whereHas('amostra.requisicao', fn ($r) => $r->where('laboratorio_id', $laboratorio->id))))
                    ->each(function (BoletimInterno $boletim) use (&$count, $laboratorio, $prazoEmHoras) {
                        $prazo = Carbon::parse($boletim->entrada_em)->addHours($prazoEmHoras);
                        if (now()->greaterThan($prazo)) {
                            $count += $this->registar(
                                'slaBreach',
                                "Quebra de SLA — {$laboratorio->name}",
                                1,
                                1,
                                $laboratorio->sla_severity ?? 'warning',
                                'boletim_interno',
                                $boletim->id,
                            ) ? 1 : 0;
                        }
                    });
            });

        return $count;
    }

    private function registar(
        string $metricKey,
        string $label,
        float $value,
        float $threshold,
        string $tone,
        string $entityType,
        string $entityId,
    ): bool {
        $jaExiste = DashboardAlertHistory::query()
            ->where('metric_key', $metricKey)
            ->where('entity_id', $entityId)
            ->where('action_status', '!=', 'resolvido')
            ->exists();

        if ($jaExiste) {
            return false;
        }

        DashboardAlertHistory::query()->create([
            'id' => (string) Str::uuid(),
            'metric_key' => $metricKey,
            'label' => $label,
            'value' => $value,
            'threshold' => $threshold,
            'tone' => $tone,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'origem' => 'sistema',
            'user_id' => null,
            'action_status' => 'pendente',
            'created_at' => now(),
        ]);

        return true;
    }
}

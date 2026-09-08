<?php

namespace App\Console\Commands;

use App\Models\DashboardAlertHistory;
use App\Models\InseminationRecord;
use App\Models\Insumo;
use App\Models\Lote;
use App\Models\NaoConformidade;
use Illuminate\Console\Command;
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

        $this->info("Alertas gerados: {$lotes} de lotes, {$insumos} de insumos, {$ncs} de não conformidades, {$partos} de partos previstos.");

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

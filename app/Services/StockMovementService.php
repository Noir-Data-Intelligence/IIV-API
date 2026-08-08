<?php

namespace App\Services;

use App\Models\StockItem;
use App\Models\StockMovement;
use Illuminate\Support\Str;

/**
 * Regra de negócio no SERVIDOR (fiel a `mocks/handlers/stock.ts`
 * `applyMovementDelta`): registar um movimento actualiza a quantidade do
 * item afectado — nunca no cliente. Apagar um movimento reverte esse efeito.
 * `entrada`/`ajuste` somam, `saida` subtrai, `transferencia` não altera o
 * total em mão (só muda localização). A quantidade nunca desce abaixo de 0.
 */
class StockMovementService
{
    public function registar(array $columns): StockMovement
    {
        $movimento = StockMovement::query()->create([
            'id' => (string) Str::uuid(),
            ...$columns,
            'created_at' => now(),
        ]);

        $this->aplicarQuantidade($movimento->item, $this->delta($movimento->type, $movimento->quantity));

        return $movimento;
    }

    public function reverter(StockMovement $movimento): void
    {
        $this->aplicarQuantidade($movimento->item, -$this->delta($movimento->type, $movimento->quantity));
        $movimento->delete();
    }

    private function delta(string $type, float $quantity): float
    {
        return match ($type) {
            'entrada', 'ajuste' => $quantity,
            'saida' => -$quantity,
            default => 0.0,
        };
    }

    private function aplicarQuantidade(StockItem $item, float $delta): void
    {
        $item->update(['quantity' => max(0, $item->quantity + $delta)]);
    }
}

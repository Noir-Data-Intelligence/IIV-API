<?php

namespace Database\Seeders;

use App\Models\EvaluationCycle;
use Illuminate\Database\Seeder;

/**
 * Ciclo de avaliação de desempenho do ano corrente, já `aberto` — sem esta
 * semente, "Novo Critério" (Avaliação de Desempenho) fica sem nenhum ciclo
 * para associar e a submissão falha na validação obrigatória de `cycleId`,
 * dando a sensação de "não permite criar" (ver auditoria funcional).
 * `updateOrCreate` por ano — seguro para re-correr.
 */
class EvaluationCycleSeeder extends Seeder
{
    public function run(): void
    {
        $year = (int) now()->year;

        EvaluationCycle::query()->updateOrCreate(
            ['year' => $year],
            [
                'name' => "Ciclo de Avaliação {$year}",
                'start_date' => now()->startOfYear()->toDateString(),
                'end_date' => now()->endOfYear()->toDateString(),
                'status' => 'aberto',
                'created_at' => now(),
            ],
        );
    }
}

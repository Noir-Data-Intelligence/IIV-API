<?php

namespace Tests\Feature;

use App\Models\Estacao;
use App\Models\EvaluationCycle;
use Database\Seeders\EstacaoSeeder;
use Database\Seeders\EvaluationCycleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeedersTest extends TestCase
{
    use RefreshDatabase;

    public function test_estacao_seeder_cria_estacoes_e_e_idempotente(): void
    {
        $this->seed(EstacaoSeeder::class);
        $count = Estacao::query()->count();
        $this->assertGreaterThan(0, $count);

        $this->seed(EstacaoSeeder::class);
        $this->assertSame($count, Estacao::query()->count());
    }

    public function test_evaluation_cycle_seeder_cria_ciclo_aberto_do_ano_corrente_e_e_idempotente(): void
    {
        $this->seed(EvaluationCycleSeeder::class);
        $this->assertDatabaseHas('evaluation_cycles', ['year' => (int) now()->year, 'status' => 'aberto']);
        $count = EvaluationCycle::query()->count();

        $this->seed(EvaluationCycleSeeder::class);
        $this->assertSame($count, EvaluationCycle::query()->count());
    }
}

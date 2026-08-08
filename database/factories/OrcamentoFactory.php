<?php

namespace Database\Factories;

use App\Models\Conta;
use App\Models\Orcamento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Orcamento>
 */
class OrcamentoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'year' => (int) fake()->year(),
            'account_id' => Conta::factory(),
            'department_id' => null,
            'planned_amount' => fake()->randomFloat(2, 10000, 1000000),
            'notes' => fake()->optional()->sentence(),
            'created_at' => now(),
        ];
    }
}

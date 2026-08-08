<?php

namespace Database\Factories;

use App\Models\Conta;
use App\Models\Lancamento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lancamento>
 */
class LancamentoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'account_id' => Conta::factory(),
            'department_id' => null,
            'type' => fake()->randomElement(['receita', 'despesa']),
            'amount' => fake()->randomFloat(2, 1000, 500000),
            'currency' => 'AOA',
            'transaction_date' => fake()->date(),
            'description' => fake()->sentence(),
            'reference' => fake()->optional()->bothify('REF-####'),
            'status' => fake()->randomElement(['pendente', 'pago', 'cancelado']),
            'notes' => fake()->optional()->sentence(),
            'created_at' => now(),
        ];
    }
}

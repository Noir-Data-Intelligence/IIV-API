<?php

namespace Database\Factories;

use App\Models\PatrimonioEstacao;
use App\Models\PatrimonioEstacaoManutencao;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PatrimonioEstacaoManutencao>
 */
class PatrimonioEstacaoManutencaoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patrimonio_estacao_id' => PatrimonioEstacao::factory(),
            'date' => fake()->date(),
            'type' => fake()->randomElement(['preventiva', 'correctiva']),
            'description' => fake()->sentence(),
            'cost' => fake()->randomFloat(2, 0, 50000),
            'provider' => fake()->optional()->company(),
            'next_due_date' => fake()->optional()->date(),
            'notes' => fake()->optional()->sentence(),
            'created_at' => now(),
        ];
    }
}

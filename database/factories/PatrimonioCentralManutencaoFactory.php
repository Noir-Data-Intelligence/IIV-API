<?php

namespace Database\Factories;

use App\Models\PatrimonioCentral;
use App\Models\PatrimonioCentralManutencao;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PatrimonioCentralManutencao>
 */
class PatrimonioCentralManutencaoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patrimonio_central_id' => PatrimonioCentral::factory(),
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

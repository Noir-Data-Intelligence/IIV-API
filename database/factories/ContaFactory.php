<?php

namespace Database\Factories;

use App\Models\Conta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Conta>
 */
class ContaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->numerify('#.#.##'),
            'name' => fake()->words(3, true),
            'type' => fake()->randomElement(['receita', 'despesa']),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
            'created_at' => now(),
        ];
    }
}

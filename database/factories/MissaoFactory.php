<?php

namespace Database\Factories;

use App\Models\Missao;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Missao>
 */
class MissaoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'destination' => fake()->city(),
            'purpose' => fake()->optional()->sentence(),
            'start_date' => fake()->date(),
            'end_date' => fake()->date(),
            'status' => 'planeada',
            'budget' => fake()->randomFloat(2, 10000, 500000),
            'currency' => 'AOA',
            'notes' => fake()->optional()->sentence(),
            'created_by' => null,
            'created_at' => now(),
        ];
    }
}

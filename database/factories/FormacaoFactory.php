<?php

namespace Database\Factories;

use App\Models\Formacao;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Formacao>
 */
class FormacaoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'trainer' => fake()->optional()->name(),
            'location' => fake()->optional()->city(),
            'start_date' => fake()->date(),
            'end_date' => fake()->date(),
            'hours' => fake()->randomFloat(1, 2, 40),
            'status' => 'planeada',
            'notes' => fake()->optional()->sentence(),
            'created_at' => now(),
        ];
    }
}

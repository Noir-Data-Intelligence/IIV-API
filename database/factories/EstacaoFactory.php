<?php

namespace Database\Factories;

use App\Models\Estacao;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Estacao>
 */
class EstacaoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Estação '.fake()->unique()->city(),
            'station_type' => fake()->randomElement(['zootecnica', 'experimental', 'campo']),
            'location' => fake()->optional()->city(),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
            'created_at' => now(),
        ];
    }
}

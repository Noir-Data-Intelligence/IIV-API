<?php

namespace Database\Factories;

use App\Models\Breeder;
use App\Models\IaCenter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Breeder>
 */
class BreederFactory extends Factory
{
    public function definition(): array
    {
        return [
            'center_id' => IaCenter::factory(),
            'tag' => fake()->unique()->bothify('REP-####'),
            'name' => fake()->optional()->firstName(),
            'species' => fake()->randomElement(['bovino', 'suino', 'caprino', 'ovino']),
            'breed' => fake()->optional()->word(),
            'birth_date' => fake()->optional()->date(),
            'status' => 'activo',
            'notes' => fake()->optional()->sentence(),
            'created_at' => now(),
        ];
    }
}

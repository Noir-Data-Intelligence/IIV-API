<?php

namespace Database\Factories;

use App\Models\ProcessType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProcessType>
 */
class ProcessTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'icon' => null,
            'sla_days' => fake()->optional()->numberBetween(1, 30),
            'is_active' => true,
            'created_at' => now(),
        ];
    }
}

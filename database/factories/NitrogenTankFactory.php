<?php

namespace Database\Factories;

use App\Models\IaCenter;
use App\Models\NitrogenTank;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NitrogenTank>
 */
class NitrogenTankFactory extends Factory
{
    public function definition(): array
    {
        return [
            'center_id' => IaCenter::factory(),
            'code' => fake()->unique()->bothify('TQ-####'),
            'capacity_l' => fake()->randomFloat(2, 20, 200),
            'current_level_l' => fake()->randomFloat(2, 0, 100),
            'min_level_l' => fake()->randomFloat(2, 5, 20),
            'last_refill_date' => fake()->optional()->date(),
            'notes' => fake()->optional()->sentence(),
            'created_at' => now(),
        ];
    }
}

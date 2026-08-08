<?php

namespace Database\Factories;

use App\Models\Field;
use App\Models\Harvest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Harvest>
 */
class HarvestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'field_id' => Field::factory(),
            'harvest_date' => fake()->date(),
            'quantity' => fake()->randomFloat(2, 10, 5000),
            'unit' => 'kg',
            'quality_grade' => fake()->optional()->randomElement(['A', 'B', 'C']),
            'notes' => fake()->optional()->sentence(),
            'created_at' => now(),
        ];
    }
}

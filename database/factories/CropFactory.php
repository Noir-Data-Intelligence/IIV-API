<?php

namespace Database\Factories;

use App\Models\Crop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Crop>
 */
class CropFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Milho', 'Soja', 'Capim-elefante', 'Batata-doce', 'Mandioca', 'Inhame']),
            'scientific_name' => fake()->optional()->words(2, true),
            'cycle_days' => fake()->optional()->numberBetween(60, 240),
            'notes' => fake()->optional()->sentence(),
            'created_at' => now(),
        ];
    }
}

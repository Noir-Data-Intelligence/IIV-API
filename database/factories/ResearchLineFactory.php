<?php

namespace Database\Factories;

use App\Models\ResearchLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResearchLine>
 */
class ResearchLineFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Epidemiologia', 'Genética Animal', 'Resistência Antimicrobiana', 'Virologia', 'Parasitologia']),
            'area' => fake()->optional()->word(),
            'description' => fake()->optional()->sentence(),
            'status' => 'activa',
            'created_by' => null,
            'created_at' => now(),
        ];
    }
}

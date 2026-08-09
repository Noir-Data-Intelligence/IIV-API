<?php

namespace Database\Factories;

use App\Models\ResearchLine;
use App\Models\ResearchProject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResearchProject>
 */
class ResearchProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'line_id' => ResearchLine::factory(),
            'title' => fake()->sentence(4),
            'objectives' => fake()->optional()->paragraph(),
            'status' => 'proposto',
            'start_date' => fake()->optional()->date(),
            'end_date' => fake()->optional()->date(),
            'funding_source' => fake()->optional()->randomElement(['FAO', 'WOAH', 'MINAGRIF', 'PATTEC']),
            'funding_amount' => fake()->optional()->randomFloat(2, 100000, 5000000),
            'currency' => 'AOA',
            'partners' => fake()->optional()->sentence(),
            'created_by' => null,
            'created_at' => now(),
        ];
    }
}

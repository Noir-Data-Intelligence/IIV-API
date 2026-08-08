<?php

namespace Database\Factories;

use App\Models\EvaluationCriteria;
use App\Models\EvaluationCycle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EvaluationCriteria>
 */
class EvaluationCriteriaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'cycle_id' => EvaluationCycle::factory(),
            'name' => fake()->randomElement(['Assiduidade', 'Qualidade técnica', 'Trabalho em equipa', 'Iniciativa']),
            'weight' => fake()->randomFloat(2, 5, 40),
            'description' => fake()->optional()->sentence(),
            'display_order' => fake()->numberBetween(0, 10),
            'created_at' => now(),
        ];
    }
}

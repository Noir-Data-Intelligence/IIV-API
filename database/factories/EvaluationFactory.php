<?php

namespace Database\Factories;

use App\Models\Evaluation;
use App\Models\EvaluationCycle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Evaluation>
 */
class EvaluationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'cycle_id' => EvaluationCycle::factory(),
            'employee_id' => fake()->uuid(),
            'evaluator_id' => null,
            'evaluation_date' => null,
            'global_score' => null,
            'strengths' => fake()->optional()->sentence(),
            'improvements' => fake()->optional()->sentence(),
            'general_comments' => fake()->optional()->sentence(),
            'status' => 'rascunho',
            'submitted_at' => null,
            'approved_at' => null,
            'approved_by' => null,
            'rejection_reason' => null,
            'acknowledged_at' => null,
            'created_at' => now(),
        ];
    }
}

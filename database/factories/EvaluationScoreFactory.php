<?php

namespace Database\Factories;

use App\Models\Evaluation;
use App\Models\EvaluationCriteria;
use App\Models\EvaluationScore;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EvaluationScore>
 */
class EvaluationScoreFactory extends Factory
{
    public function definition(): array
    {
        return [
            'evaluation_id' => Evaluation::factory(),
            'criteria_id' => EvaluationCriteria::factory(),
            'score' => fake()->randomFloat(2, 0, 20),
            'comment' => fake()->optional()->sentence(),
        ];
    }
}

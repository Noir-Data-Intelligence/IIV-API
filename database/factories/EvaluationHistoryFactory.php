<?php

namespace Database\Factories;

use App\Models\Evaluation;
use App\Models\EvaluationHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EvaluationHistory>
 */
class EvaluationHistoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'evaluation_id' => Evaluation::factory(),
            'actor_id' => null,
            'action' => 'criou',
            'from_status' => null,
            'to_status' => 'rascunho',
            'comment' => fake()->optional()->sentence(),
            'changes' => null,
            'created_at' => now(),
        ];
    }
}

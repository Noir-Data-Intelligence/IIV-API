<?php

namespace Database\Factories;

use App\Models\EvaluationCycle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EvaluationCycle>
 */
class EvaluationCycleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Ciclo '.fake()->year(),
            'year' => (int) fake()->year(),
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => 'planeado',
            'description' => fake()->optional()->sentence(),
            'created_at' => now(),
        ];
    }
}

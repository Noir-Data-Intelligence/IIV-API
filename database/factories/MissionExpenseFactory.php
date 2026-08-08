<?php

namespace Database\Factories;

use App\Models\Missao;
use App\Models\MissionExpense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MissionExpense>
 */
class MissionExpenseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'mission_id' => Missao::factory(),
            'category' => fake()->randomElement(['transporte', 'alojamento', 'alimentacao', 'combustivel', 'outro']),
            'description' => fake()->optional()->sentence(),
            'amount' => fake()->randomFloat(2, 1000, 100000),
            'currency' => 'AOA',
            'expense_date' => fake()->date(),
            'receipt_url' => null,
            'created_at' => now(),
        ];
    }
}

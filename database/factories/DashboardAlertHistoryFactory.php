<?php

namespace Database\Factories;

use App\Models\DashboardAlertHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DashboardAlertHistory>
 */
class DashboardAlertHistoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'metric_key' => fake()->randomElement(['taxaRejeicao', 'amostrasHoje', 'slaVencido']),
            'label' => fake()->sentence(3),
            'value' => fake()->randomFloat(2, 0, 100),
            'threshold' => fake()->randomFloat(2, 0, 100),
            'tone' => fake()->randomElement(['warning', 'critical']),
            'action_status' => 'pendente',
            'created_at' => now(),
        ];
    }
}

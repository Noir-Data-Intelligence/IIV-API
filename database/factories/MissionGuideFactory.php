<?php

namespace Database\Factories;

use App\Models\Missao;
use App\Models\MissionGuide;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MissionGuide>
 */
class MissionGuideFactory extends Factory
{
    public function definition(): array
    {
        return [
            'mission_id' => Missao::factory(),
            'guide_number' => fake()->unique()->bothify('GM-####'),
            'issue_date' => fake()->date(),
            'per_diem' => fake()->randomFloat(2, 5000, 50000),
            'transport' => fake()->optional()->randomElement(['viatura institucional', 'avião', 'transporte público']),
            'notes' => fake()->optional()->sentence(),
            'created_at' => now(),
        ];
    }
}

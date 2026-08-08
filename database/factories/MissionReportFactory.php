<?php

namespace Database\Factories;

use App\Models\Missao;
use App\Models\MissionReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MissionReport>
 */
class MissionReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'mission_id' => Missao::factory(),
            'report_date' => fake()->date(),
            'summary' => fake()->optional()->paragraph(),
            'outcomes' => fake()->optional()->paragraph(),
            'status' => 'rascunho',
            'submitted_by' => null,
            'approved_by' => null,
            'approved_at' => null,
            'created_at' => now(),
        ];
    }
}

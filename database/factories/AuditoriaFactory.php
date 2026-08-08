<?php

namespace Database\Factories;

use App\Models\Auditoria;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Auditoria>
 */
class AuditoriaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'audit_type' => fake()->randomElement(['interna', 'externa', 'ISO']),
            'auditor' => fake()->name(),
            'scheduled_date' => fake()->date(),
            'completed_date' => null,
            'status' => 'planeada',
            'findings' => fake()->optional()->paragraph(),
            'recommendations' => fake()->optional()->paragraph(),
            'department_id' => null,
            'laboratory_id' => null,
            'created_at' => now(),
        ];
    }
}

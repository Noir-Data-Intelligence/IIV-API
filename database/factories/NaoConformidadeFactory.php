<?php

namespace Database\Factories;

use App\Models\NaoConformidade;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NaoConformidade>
 */
class NaoConformidadeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'severity' => fake()->randomElement(['menor', 'maior', 'critica']),
            'status' => 'aberta',
            'corrective_action' => null,
            'deadline' => null,
            'resolved_at' => null,
            'department_id' => null,
            'audit_id' => null,
            'created_at' => now(),
        ];
    }
}

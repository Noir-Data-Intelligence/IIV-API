<?php

namespace Database\Factories;

use App\Models\RhLaboratorioColaborador;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RhLaboratorioColaborador>
 */
class RhLaboratorioColaboradorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_number' => fake()->unique()->bothify('LAB-####'),
            'full_name' => fake()->name(),
            'national_id' => fake()->optional()->bothify('#########LA###'),
            'phone' => fake()->optional()->phoneNumber(),
            'email' => fake()->optional()->safeEmail(),
            'department_id' => null,
            'hire_date' => fake()->date(),
            'is_active' => true,
            'qualifications' => fake()->optional()->sentence(),
            'notes' => fake()->optional()->sentence(),
            'created_at' => now(),
        ];
    }
}

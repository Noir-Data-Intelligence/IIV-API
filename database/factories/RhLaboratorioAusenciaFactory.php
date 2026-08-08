<?php

namespace Database\Factories;

use App\Models\RhLaboratorioAusencia;
use App\Models\RhLaboratorioColaborador;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RhLaboratorioAusencia>
 */
class RhLaboratorioAusenciaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => RhLaboratorioColaborador::factory(),
            'leave_type' => fake()->randomElement(['ferias', 'doenca', 'maternidade', 'paternidade', 'luto', 'sem_vencimento', 'outro']),
            'start_date' => fake()->date(),
            'end_date' => fake()->date(),
            'days' => fake()->numberBetween(1, 30),
            'status' => 'pendente',
            'reason' => fake()->optional()->sentence(),
            'created_at' => now(),
        ];
    }
}

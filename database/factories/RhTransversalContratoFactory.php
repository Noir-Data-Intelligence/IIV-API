<?php

namespace Database\Factories;

use App\Models\RhTransversalColaborador;
use App\Models\RhTransversalContrato;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RhTransversalContrato>
 */
class RhTransversalContratoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => RhTransversalColaborador::factory(),
            'contract_type' => fake()->randomElement(['efectivo', 'termo_certo', 'termo_incerto', 'prestacao_servicos', 'estagio']),
            'position' => fake()->jobTitle(),
            'start_date' => fake()->date(),
            'end_date' => null,
            'salary' => fake()->randomFloat(2, 50000, 800000),
            'currency' => 'AOA',
            'is_active' => true,
            'notes' => fake()->optional()->sentence(),
            'created_at' => now(),
        ];
    }
}

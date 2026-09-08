<?php

namespace Database\Factories;

use App\Models\Estacao;
use App\Models\PatrimonioEstacao;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PatrimonioEstacao>
 */
class PatrimonioEstacaoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('PAT-EST-####'),
            'name' => fake()->words(3, true),
            'category' => fake()->randomElement(['equipamento', 'mobiliario', 'informatica', 'viatura', 'outros']),
            // Descrição obrigatória em Estação (ao contrário de Central) — nunca opcional aqui.
            'description' => fake()->sentence(),
            'location' => fake()->optional()->words(2, true),
            'station_id' => Estacao::factory(),
            'department_id' => null,
            'responsible_user_id' => null,
            'acquisition_date' => fake()->date(),
            'acquisition_cost' => fake()->randomFloat(2, 1000, 500000),
            'current_value' => fake()->optional()->randomFloat(2, 500, 400000),
            'serial_number' => fake()->optional()->bothify('SN-########'),
            'status' => 'activo',
            'notes' => fake()->optional()->sentence(),
        ];
    }
}

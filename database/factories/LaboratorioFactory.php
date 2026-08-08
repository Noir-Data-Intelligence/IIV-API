<?php

namespace Database\Factories;

use App\Models\Laboratorio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Laboratorio>
 */
class LaboratorioFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('??')),
            'name' => fake()->words(3, true),
            'type' => 'clinico',
            'description' => fake()->optional()->sentence(),
            'validador_count' => 2,
            'sla_horas' => 72,
            'validadores_nomeados' => null,
            'is_active' => true,
        ];
    }
}

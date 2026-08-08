<?php

namespace Database\Factories;

use App\Models\CriterioRejeicao;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CriterioRejeicao>
 */
class CriterioRejeicaoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'laboratorio_id' => null,
            'codigo' => strtoupper(fake()->unique()->lexify('REJ-???')),
            'motivo' => fake()->sentence(),
        ];
    }
}

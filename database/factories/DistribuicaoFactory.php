<?php

namespace Database\Factories;

use App\Models\Distribuicao;
use App\Models\Lote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Distribuicao>
 */
class DistribuicaoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'batch_id' => Lote::factory(),
            'destination' => fake()->city(),
            'quantity' => fake()->numberBetween(10, 500),
            'distribution_date' => fake()->date(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}

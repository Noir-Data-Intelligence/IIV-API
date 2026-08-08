<?php

namespace Database\Factories;

use App\Models\Estacao;
use App\Models\ProducaoPecuaria;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProducaoPecuaria>
 */
class ProducaoPecuariaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'station_id' => Estacao::factory(),
            'product_type' => fake()->randomElement(['Leite', 'Ovos', 'Carne', 'Mel', 'Outro']),
            'production_date' => fake()->date(),
            'quantity' => fake()->randomFloat(2, 1, 1000),
            'unit' => 'kg',
            'recorded_by' => fake()->optional()->name(),
            'notes' => fake()->optional()->sentence(),
            'created_at' => now(),
        ];
    }
}

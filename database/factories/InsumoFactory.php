<?php

namespace Database\Factories;

use App\Models\Insumo;
use App\Models\Laboratorio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Insumo>
 */
class InsumoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'laboratorio_id' => Laboratorio::factory(),
            'name' => fake()->unique()->words(2, true),
            'quantity' => fake()->numberBetween(0, 200),
            'unit' => fake()->randomElement(['ml', 'unidade', 'frasco', 'kg']),
            'min_stock' => fake()->numberBetween(0, 20),
            'expiry_date' => fake()->optional()->dateTimeBetween('now', '+2 years'),
        ];
    }
}

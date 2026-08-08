<?php

namespace Database\Factories;

use App\Models\Plano;
use App\Models\Produto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plano>
 */
class PlanoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Produto::factory(),
            'planned_quantity' => fake()->numberBetween(100, 5000),
            'actual_quantity' => null,
            'planned_start' => fake()->date(),
            'planned_end' => fake()->dateTimeBetween('+1 month', '+3 months')->format('Y-m-d'),
            'status' => 'planeada',
            'notes' => fake()->optional()->sentence(),
        ];
    }
}

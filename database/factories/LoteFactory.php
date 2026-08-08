<?php

namespace Database\Factories;

use App\Models\Lote;
use App\Models\Produto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lote>
 */
class LoteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'batch_number' => 'LOTE-'.fake()->unique()->numerify('####'),
            'product_id' => Produto::factory(),
            'quantity_produced' => fake()->numberBetween(100, 5000),
            'quantity_distributed' => 0,
            'production_date' => fake()->date(),
            'expiry_date' => fake()->dateTimeBetween('+6 months', '+2 years')->format('Y-m-d'),
            'status' => 'planeada',
            'notes' => fake()->optional()->sentence(),
            'created_at' => now(),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\StockItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockItem>
 */
class StockItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'category' => fake()->randomElement(['laboratorio', 'vacinas', 'agricola', 'pecuaria', 'administrativo', 'semen', 'combustivel']),
            'sku' => fake()->optional()->bothify('SKU-####'),
            'unit' => fake()->randomElement(['unidade', 'litro', 'kg', 'caixa']),
            'quantity' => fake()->randomFloat(2, 0, 500),
            'min_stock' => fake()->randomFloat(2, 0, 50),
            'location_id' => null,
            'expiry_date' => fake()->optional()->date(),
            'supplier' => fake()->optional()->company(),
            'unit_cost' => fake()->optional()->randomFloat(2, 100, 50000),
            'notes' => fake()->optional()->sentence(),
            'created_at' => now(),
        ];
    }
}

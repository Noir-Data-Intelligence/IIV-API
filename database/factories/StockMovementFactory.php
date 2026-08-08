<?php

namespace Database\Factories;

use App\Models\StockItem;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'item_id' => StockItem::factory(),
            'type' => fake()->randomElement(['entrada', 'saida', 'transferencia', 'ajuste']),
            'quantity' => fake()->randomFloat(2, 1, 100),
            'from_location_id' => null,
            'to_location_id' => null,
            'reason' => fake()->optional()->sentence(),
            'performed_by' => fake()->optional()->name(),
            'movement_date' => fake()->date(),
            'created_at' => now(),
        ];
    }
}

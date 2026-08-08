<?php

namespace Database\Factories;

use App\Models\Breeder;
use App\Models\SemenDose;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SemenDose>
 */
class SemenDoseFactory extends Factory
{
    public function definition(): array
    {
        $quantity = fake()->numberBetween(5, 50);

        return [
            'breeder_id' => Breeder::factory(),
            'tank_id' => null,
            'collection_date' => fake()->date(),
            'quantity' => $quantity,
            'available_quantity' => $quantity,
            'quality_grade' => fake()->randomElement(['A', 'B', 'C']),
            'notes' => fake()->optional()->sentence(),
            'created_at' => now(),
        ];
    }
}

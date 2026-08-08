<?php

namespace Database\Factories;

use App\Models\Produto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Produto>
 */
class ProdutoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'product_type' => fake()->randomElement(['vacina', 'soro', 'reagente']),
            'description' => fake()->optional()->sentence(),
            'unit' => fake()->randomElement(['dose', 'ml', 'frasco']),
            'is_archived' => false,
            'created_at' => now(),
        ];
    }
}

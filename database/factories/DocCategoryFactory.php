<?php

namespace Database\Factories;

use App\Models\DocCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocCategory>
 */
class DocCategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'icon' => null,
            'sort_order' => fake()->numberBetween(1, 10),
        ];
    }
}

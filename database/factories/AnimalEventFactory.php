<?php

namespace Database\Factories;

use App\Models\Animal;
use App\Models\AnimalEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnimalEvent>
 */
class AnimalEventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'animal_id' => Animal::factory(),
            'event_type' => fake()->randomElement(['nascimento', 'observacao', 'transferencia', 'venda']),
            'event_date' => fake()->date(),
            'notes' => fake()->optional()->sentence(),
            'created_at' => now(),
        ];
    }
}

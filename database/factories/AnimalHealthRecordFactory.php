<?php

namespace Database\Factories;

use App\Models\Animal;
use App\Models\AnimalHealthRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnimalHealthRecord>
 */
class AnimalHealthRecordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'animal_id' => Animal::factory(),
            'record_type' => fake()->randomElement(['vacina', 'tratamento', 'exame']),
            'product_name' => fake()->optional()->word(),
            'dosage' => fake()->optional()->bothify('## ml'),
            'diagnosis' => fake()->optional()->sentence(),
            'treatment' => fake()->optional()->sentence(),
            'veterinarian' => fake()->optional()->name(),
            'record_date' => fake()->date(),
            'next_due_date' => fake()->optional()->date(),
            'created_at' => now(),
        ];
    }
}

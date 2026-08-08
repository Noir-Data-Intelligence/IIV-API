<?php

namespace Database\Factories;

use App\Models\Animal;
use App\Models\InseminationRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InseminationRecord>
 */
class InseminationRecordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'animal_id' => Animal::factory(),
            'dose_id' => null,
            'technician_id' => null,
            'insemination_date' => fake()->date(),
            'result' => 'pendente',
            'pregnancy_confirmed_at' => null,
            'expected_birth_date' => null,
            'notes' => fake()->optional()->sentence(),
            'created_at' => now(),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\PatrimonioCentral;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PatrimonioCentral>
 */
class PatrimonioCentralFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('PAT-CTR-####'),
            'name' => fake()->words(3, true),
            'category' => fake()->randomElement(['equipamento', 'mobiliario', 'informatica', 'viatura', 'outros']),
            'description' => fake()->optional()->sentence(),
            'location' => fake()->optional()->words(2, true),
            'department_id' => null,
            'responsible_user_id' => null,
            'acquisition_date' => fake()->date(),
            'acquisition_cost' => fake()->randomFloat(2, 1000, 500000),
            'current_value' => fake()->optional()->randomFloat(2, 500, 400000),
            'serial_number' => fake()->optional()->bothify('SN-########'),
            'status' => 'activo',
            'notes' => fake()->optional()->sentence(),
        ];
    }
}

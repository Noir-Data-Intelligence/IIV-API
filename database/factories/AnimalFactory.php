<?php

namespace Database\Factories;

use App\Models\Animal;
use App\Models\Estacao;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Animal>
 */
class AnimalFactory extends Factory
{
    public function definition(): array
    {
        $sex = fake()->randomElement(['macho', 'femea']);

        return [
            'station_id' => Estacao::factory(),
            'tag' => fake()->unique()->bothify('AN-####'),
            'name' => fake()->optional()->firstName(),
            'species' => fake()->randomElement(['bovino', 'suino', 'caprino', 'ovino', 'avicola']),
            'breed' => fake()->optional()->word(),
            'sex' => $sex,
            // Só faz sentido para fêmeas (CT-012).
            'reproductive_status' => $sex === 'femea'
                ? fake()->optional()->randomElement(['vazia', 'cio', 'coberta', 'gestante', 'lactante'])
                : null,
            'birth_date' => fake()->optional()->date(),
            'mother_tag' => fake()->optional()->bothify('AN-####'),
            'father_tag' => fake()->optional()->bothify('AN-####'),
            'status' => 'activo',
            'current_weight_kg' => fake()->optional()->randomFloat(2, 5, 800),
            'notes' => fake()->optional()->sentence(),
            'created_at' => now(),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Crop;
use App\Models\Estacao;
use App\Models\Field;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Field>
 */
class FieldFactory extends Factory
{
    public function definition(): array
    {
        return [
            'station_id' => Estacao::factory(),
            'crop_id' => Crop::factory(),
            'field_code' => fake()->optional()->bothify('CMP-##'),
            'area_ha' => fake()->randomFloat(2, 0.5, 50),
            'planting_date' => fake()->optional()->date(),
            'expected_harvest' => fake()->optional()->date(),
            'status' => 'planeado',
            'notes' => fake()->optional()->sentence(),
            'created_at' => now(),
        ];
    }
}

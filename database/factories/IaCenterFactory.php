<?php

namespace Database\Factories;

use App\Models\IaCenter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IaCenter>
 */
class IaCenterFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Centro de IA '.fake()->unique()->city(),
            'location' => fake()->optional()->city(),
            'responsible_user_id' => null,
            'notes' => fake()->optional()->sentence(),
            'is_active' => true,
            'created_at' => now(),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Missao;
use App\Models\MissionParticipant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MissionParticipant>
 */
class MissionParticipantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'mission_id' => Missao::factory(),
            'user_id' => User::factory(),
            'role' => fake()->randomElement(['chefe_missao', 'tecnico', 'motorista', 'observador']),
            'per_diem' => fake()->randomFloat(2, 5000, 50000),
            'created_at' => now(),
        ];
    }
}

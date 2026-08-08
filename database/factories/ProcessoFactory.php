<?php

namespace Database\Factories;

use App\Models\Processo;
use App\Models\ProcessType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Processo>
 */
class ProcessoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => 'PRC-'.now()->year.'-'.fake()->unique()->numerify('####'),
            'type_id' => ProcessType::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'requester_id' => User::factory(),
            'status' => 'aberto',
            'priority' => 'normal',
            'due_date' => null,
            'opened_at' => now(),
            'closed_at' => null,
            'current_step_id' => null,
            'linked_entity_type' => null,
            'linked_entity_id' => null,
            'created_at' => now(),
        ];
    }
}

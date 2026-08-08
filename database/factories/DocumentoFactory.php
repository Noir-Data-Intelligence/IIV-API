<?php

namespace Database\Factories;

use App\Models\Documento;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Documento>
 */
class DocumentoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'category_id' => null,
            'owner_id' => User::factory(),
            'status' => 'rascunho',
            'visibility' => 'publico',
            'expiry_date' => null,
            'tags' => [],
            'current_version_id' => null,
        ];
    }
}

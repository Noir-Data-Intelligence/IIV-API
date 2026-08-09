<?php

namespace Database\Factories;

use App\Models\Publication;
use App\Models\ResearchProject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Publication>
 */
class PublicationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => ResearchProject::factory(),
            'type' => fake()->randomElement(['artigo', 'comunicacao', 'livro', 'tese', 'relatorio']),
            'title' => fake()->sentence(6),
            'authors' => [fake()->name(), fake()->name()],
            'year' => fake()->optional()->numberBetween(2015, 2026),
            'venue' => fake()->optional()->sentence(3),
            'doi' => fake()->optional()->bothify('10.####/????'),
            'url' => fake()->optional()->url(),
            'created_by' => null,
            'created_at' => now(),
        ];
    }
}

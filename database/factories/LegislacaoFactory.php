<?php

namespace Database\Factories;

use App\Models\Legislacao;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Legislacao>
 */
class LegislacaoFactory extends Factory
{
    public function definition(): array
    {
        $titulo = fake()->sentence(5);

        return [
            'num' => fake()->unique()->numerify('##/##'),
            'slug' => Str::slug($titulo).'-'.fake()->unique()->numberBetween(1, 99999),
            'titulo' => $titulo,
            'descricao' => fake()->sentence(15),
            'tipo' => fake()->randomElement(['Lei', 'Decreto', 'Regulamento', 'Norma', 'Portaria']),
            'ano' => (string) fake()->numberBetween(2015, 2026),
            'pdf_url' => null,
            'published' => true,
        ];
    }
}

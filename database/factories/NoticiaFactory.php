<?php

namespace Database\Factories;

use App\Models\Noticia;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Noticia>
 */
class NoticiaFactory extends Factory
{
    public function definition(): array
    {
        $titulo = fake()->sentence(6);

        return [
            'slug' => Str::slug($titulo).'-'.fake()->unique()->numberBetween(1, 99999),
            'titulo' => $titulo,
            'resumo' => fake()->sentence(12),
            'conteudo' => fake()->paragraphs(3, true),
            'categoria' => fake()->randomElement(['Geral', 'Vacinação', 'Infraestrutura', 'Parcerias', 'Vigilância']),
            'image_path' => null,
            'destaque' => false,
            'published' => true,
            'published_at' => now(),
        ];
    }

    public function unpublished(): static
    {
        return $this->state(['published' => false, 'published_at' => null]);
    }
}

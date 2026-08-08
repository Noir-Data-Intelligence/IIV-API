<?php

namespace Database\Factories;

use App\Models\HeroSlide;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HeroSlide>
 */
class HeroSlideFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kicker' => fake()->word(),
            'title' => fake()->sentence(4),
            'subtitle' => fake()->sentence(8),
            'cta_label' => 'Saber mais',
            'cta_link' => '/sobre',
            'image_url' => null,
            'sort_order' => fake()->numberBetween(1, 10),
            'published' => true,
        ];
    }
}

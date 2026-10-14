<?php

namespace Database\Factories;

use App\Enums\BoletimInternoStatus;
use App\Models\Amostra;
use App\Models\BoletimInterno;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BoletimInterno>
 */
class BoletimInternoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'amostra_id' => Amostra::factory(),
            'numero_analise' => 'PAT-'.fake()->unique()->numerify('###').'-'.now()->year,
            'exames' => ['solicitados' => ['hemograma']],
            'entrada_em' => now(),
            'inicio_em' => now(),
            'conclusao_em' => null,
            'status' => BoletimInternoStatus::EmAnalise,
        ];
    }
}

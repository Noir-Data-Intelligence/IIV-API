<?php

namespace Database\Factories;

use App\Enums\AmostraStatus;
use App\Models\Amostra;
use App\Models\Requisicao;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Amostra>
 */
class AmostraFactory extends Factory
{
    public function definition(): array
    {
        return [
            'requisicao_id' => Requisicao::factory(),
            'numero' => 'AM-'.fake()->unique()->numerify('#####'),
            'tipo_amostra' => fake()->randomElement(['sangue', 'soro', 'fezes', 'zaragatoa', 'tecido']),
            'origem_matriz' => fake()->word(),
            'ponto_colheita' => fake()->city(),
            'colhida_por' => fake()->name(),
            'sujeito' => ['especie' => 'Bovino', 'sexo' => 'F', 'idade' => '3 anos'],
            'recebida_em' => now(),
            'status' => AmostraStatus::Recebida,
        ];
    }
}

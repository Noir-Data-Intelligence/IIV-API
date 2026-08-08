<?php

namespace Database\Factories;

use App\Models\Laboratorio;
use App\Models\Requisicao;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Requisicao>
 */
class RequisicaoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'numero' => 'PAT-'.fake()->unique()->numerify('###').'-'.now()->year,
            'laboratorio_id' => Laboratorio::factory(),
            'tipo_sujeito' => fake()->randomElement(['animal', 'humano']),
            'cliente_nome' => fake()->name(),
            'cliente_contacto' => fake()->phoneNumber(),
            'veterinario_responsavel' => fake()->name(),
            'dados_epidemiologicos' => null,
            'dados_facturacao' => null,
            'consentimento' => true,
            'assinatura_cliente' => (string) Str::uuid(),
            'created_by' => User::factory(),
        ];
    }
}

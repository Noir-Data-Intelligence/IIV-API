<?php

namespace Database\Factories;

use App\Models\ExameCatalogo;
use App\Models\Laboratorio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExameCatalogo>
 */
class ExameCatalogoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'laboratorio_id' => Laboratorio::factory(),
            'codigo' => 'EX-'.strtoupper(fake()->unique()->bothify('???-###')),
            'nome' => fake()->words(3, true),
            'tecnica_metodo' => 'Método '.fake()->word(),
            'pop_codigo' => null,
            'tipos_amostra' => null,
            'sla_horas' => 48,
            'campos_resultado' => [['chave' => 'valor', 'tipo' => 'numero']],
            'activo' => true,
        ];
    }
}

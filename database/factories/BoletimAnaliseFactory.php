<?php

namespace Database\Factories;

use App\Models\BoletimAnalise;
use App\Models\BoletimInterno;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BoletimAnalise>
 */
class BoletimAnaliseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'boletim_interno_id' => BoletimInterno::factory(),
            'resultado' => null,
        ];
    }
}

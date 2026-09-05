<?php

namespace Database\Seeders;

use App\Models\Estacao;
use Illuminate\Database\Seeder;

/**
 * Estações zootécnicas/experimentais de arranque — sem esta semente, os
 * formulários de Produção Pecuária e Agricultura (Talhões) ficam com o botão
 * de criação permanentemente desactivado numa base de dados nova, por
 * exigirem `station_id` (ver auditoria funcional, Produção Pecuária/Agricultura).
 * `updateOrCreate` por nome — seguro para re-correr.
 */
class EstacaoSeeder extends Seeder
{
    public function run(): void
    {
        $estacoes = [
            ['Estação Zootécnica do Huambo', 'zootecnica', 'Huambo'],
            ['Estação Experimental de Luanda', 'experimental', 'Luanda'],
            ['Estação de Campo da Huíla', 'campo', 'Huíla'],
            ['Estação Zootécnica de Benguela', 'zootecnica', 'Benguela'],
            ['Estação Experimental do Bié', 'experimental', 'Bié'],
        ];

        foreach ($estacoes as [$name, $type, $location]) {
            Estacao::query()->updateOrCreate(
                ['name' => $name],
                [
                    'station_type' => $type,
                    'location' => $location,
                    'is_active' => true,
                    'created_at' => now(),
                ],
            );
        }
    }
}

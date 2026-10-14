<?php

namespace Database\Seeders;

use App\Models\Laboratorio;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * As 8 áreas laboratoriais + Sala de Incubação (REQ-005 §8,
 * SIG-IIV-MEMORIA-PROJETO.md secção 7). `validadores_nomeados` da
 * Bacteriologia (AB) usa 3 contas de demonstração plausíveis (tecnico,
 * director-laboratorio, admin) — a lista real de OMVA fica pendente do
 * cliente (RN "Bacteriologia — 3 validadores nomeados").
 */
class LaboratorioSeeder extends Seeder
{
    public function run(): void
    {
        $nomeadosAB = User::query()
            ->whereIn('email', ['tecnico@iiv.demo', 'director-laboratorio@iiv.demo', 'admin@iiv.demo'])
            ->pluck('id')
            ->all();

        $areas = [
            ['AP', 'Parasitologia', 2, null, null],
            ['AB', 'Bacteriologia Geral', 3, 72, $nomeadosAB],
            ['AS', 'Serologia', 2, null, null],
            ['BM', 'Biologia Molecular', 2, 24, null],
            ['BR', 'Bromatologia', 2, null, null],
            ['AMA', 'Microbiologia Alimentar', 2, 120, null],
            ['AR', 'Raiva', 2, null, null],
            ['AMC', 'Lavagem, Esterilização e Meios de Cultura', 2, null, null],
            ['INC', 'Sala de Incubação', 2, null, null],
        ];

        foreach ($areas as [$code, $name, $validadorCount, $slaHoras, $nomeados]) {
            Laboratorio::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'validador_count' => $validadorCount,
                    'sla_horas' => $slaHoras,
                    'validadores_nomeados' => $nomeados,
                    'is_active' => true,
                    // `responsavel_qualidade_id` fica deliberadamente NULL —
                    // a confirmar com o laboratório (D.0#5); não inventar nomes.
                    // `exige_pagamento_para_entrega` fica no default (false, D.0#4).
                ],
            );
        }

        // Demonstração: a conta tecnico@iiv.demo pertence à equipa de todas as
        // áreas, para o ecrã "A Minha Área" ter conteúdo. As equipas reais são
        // geridas em Laboratórios > Equipa (pivot `laboratorio_tecnicos`).
        $tecnicoDemo = User::query()->where('email', 'tecnico@iiv.demo')->first();
        if ($tecnicoDemo) {
            foreach (Laboratorio::query()->get() as $laboratorio) {
                $laboratorio->tecnicos()->syncWithoutDetaching([$tecnicoDemo->id => ['funcao' => 'Técnico (demo)']]);
            }
        }
    }
}

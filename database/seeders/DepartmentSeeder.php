<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

/**
 * Mesmos 10 departamentos (nomes/descrições/hierarquia) de
 * Front-end/src/mocks/fixtures/departamentos.ts, para que a API real
 * produza o mesmo ecrã que o mock produzia (ver PLANO-BACKEND-LARAVEL.txt
 * secção 9). IDs próprios (uuid), sem correspondência com os `dep-000N` do
 * mock — a hierarquia é reconstituída por nome porque os departamentos-pai
 * têm de existir antes dos filhos.
 */
class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $roots = [
            'Investigação e Desenvolvimento' => 'Coordena os programas de investigação aplicada do instituto e a articulação com parceiros científicos nacionais e internacionais.',
            'Produção Animal' => 'Apoio técnico à melhoria dos sistemas de produção pecuária, maneio e bem-estar animal.',
            'Qualidade e Metrologia' => 'Garantia da qualidade laboratorial, calibração de equipamentos e acreditação de métodos analíticos.',
            'Recursos Humanos' => 'Gestão de pessoal, formação contínua dos quadros técnicos e administração de carreiras.',
            'Financeiro e Património' => 'Gestão orçamental, contabilidade, aprovisionamento e controlo do património do instituto.',
        ];

        $ids = [];
        foreach ($roots as $name => $description) {
            $ids[$name] = Department::query()->updateOrCreate(
                ['name' => $name],
                ['description' => $description, 'parent_id' => null],
            )->id;
        }

        $children = [
            'Virologia' => ['Diagnóstico e caracterização de agentes virais animais, incluindo febre aftosa, peste suína africana e doença de Newcastle.', 'Investigação e Desenvolvimento'],
            'Bacteriologia' => ['Isolamento e identificação de agentes bacterianos, antibiogramas e vigilância da resistência antimicrobiana.', 'Investigação e Desenvolvimento'],
            'Parasitologia' => ['Estudo e diagnóstico de doenças parasitárias que afectam os efectivos pecuários, incluindo hemoparasitoses e endoparasitas.', 'Investigação e Desenvolvimento'],
            'Nutrição e Alimentação Animal' => ['Formulação de rações, avaliação de recursos alimentares locais e análise bromatológica de alimentos para animais.', 'Produção Animal'],
            'Reprodução e Genética' => ['Programas de melhoramento genético, inseminação artificial e conservação de raças autóctones.', 'Produção Animal'],
        ];

        foreach ($children as $name => [$description, $parentName]) {
            Department::query()->updateOrCreate(
                ['name' => $name],
                ['description' => $description, 'parent_id' => $ids[$parentName]],
            );
        }
    }
}

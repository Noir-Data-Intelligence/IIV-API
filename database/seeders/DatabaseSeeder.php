<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Dados de REFERÊNCIA (seguros para produção) — nunca contas de
     * utilizador. `UserSeeder` foi deliberadamente retirado daqui depois de
     * uma auditoria de segurança (red team, 2026-08-09) ter apontado que o
     * `README.md` instruía `db:seed` também em produção, o que criaria as
     * 12 contas de demonstração com passwords previsíveis (`<papel>123`)
     * em qualquer deploy real. Correr `UserSeeder` continua seguro em
     * dev/staging: `php artisan db:seed --class=UserSeeder`.
     *
     * `LaboratorioSeeder` antes de `CriterioRejeicaoSeeder` porque os
     * critérios de Microbiologia Alimentar referenciam o laboratório AMA.
     * `LaboratorioSeeder` resolve os validadores nomeados da Bacteriologia
     * (AB) por email de conta de demonstração — degrada graciosamente para
     * uma lista vazia se `UserSeeder` não tiver corrido (produção real).
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            DepartmentSeeder::class,
            LaboratorioSeeder::class,
            ExameCatalogoSeeder::class,
            CriterioRejeicaoSeeder::class,
            EstacaoSeeder::class,
            EvaluationCycleSeeder::class,
        ]);
    }
}

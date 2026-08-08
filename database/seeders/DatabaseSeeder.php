<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Onda 1 — Fundação, Onda 3 — Laboratório. Ordem importa: `UserSeeder`
     * antes de `LaboratorioSeeder` porque a Bacteriologia (AB) resolve os
     * seus 3 validadores nomeados por email de conta de demonstração;
     * `LaboratorioSeeder` antes de `CriterioRejeicaoSeeder` porque os
     * critérios de Microbiologia Alimentar referenciam o laboratório AMA.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            DepartmentSeeder::class,
            UserSeeder::class,
            LaboratorioSeeder::class,
            CriterioRejeicaoSeeder::class,
        ]);
    }
}

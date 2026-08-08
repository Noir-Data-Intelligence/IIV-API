<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Onda 1 — Fundação. Ordem importa: permissões antes de utilizadores
     * não é estritamente necessário, mas departamentos antes de utilizadores
     * seria caso se atribuísse `departmentIds` no seed (ainda não faz).
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            DepartmentSeeder::class,
            UserSeeder::class,
        ]);
    }
}

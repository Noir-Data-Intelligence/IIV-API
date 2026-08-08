<?php

namespace Database\Seeders;

use App\Enums\AppRole;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * 1 conta de demonstração por cada um dos 12 papéis, mesma convenção do
 * mock (Front-end/src/mocks/fixtures/users.ts e authCredentials.ts):
 * email `<papel>@iiv.demo`, password `<papel>123`. Mesmos nomes/telefones
 * fictícios, para o painel de acesso rápido do Login.tsx continuar a fazer
 * sentido também contra o backend real (ver SIG-IIV-MEMORIA-PROJETO.md
 * secção 9/15).
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['Fernando Kianda Ndala', AppRole::Admin, '+244 923 145 782'],
            ['Manuel Sozinho Bumba', AppRole::Diretor, '+244 916 578 934'],
            ['Adélia Kapenda Tchissola', AppRole::DirectorLaboratorio, '+244 945 210 673'],
            ['Guilhermina Sapalo Dilolwa', AppRole::ResponsavelQualidade, '+244 913 296 748'],
            ['João Baptista Sachiwo', AppRole::Tecnico, '+244 934 682 219'],
            ['Rosa Ngueve Chissano', AppRole::Recepcionista, '+244 927 843 012'],
            ['Vicente Kalombo Tchivinda', AppRole::GestorStock, '+244 936 152 908'],
            ['Filomena Ussumane Cachimbombo', AppRole::GestorPatrimonio, '+244 917 480 261'],
            ['Ernesto Palanca Zua', AppRole::GestorFinanceiro, '+244 949 720 385'],
            ['Cristina Mabeco Sengue', AppRole::GestorRh, '+244 921 065 437'],
            ['Isabel Muxito Capingala', AppRole::GestorEstacao, '+244 912 337 401'],
            ['Domingos Alberto Muanza', AppRole::Isv, '+244 938 461 590'],
        ];

        foreach ($accounts as [$fullName, $role, $phone]) {
            $email = "{$role->value}@iiv.demo";

            $user = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'full_name' => $fullName,
                    'phone' => $phone,
                    'password' => Hash::make("{$role->value}123"),
                    'email_verified_at' => now(),
                ],
            );

            UserRole::query()->firstOrCreate([
                'user_id' => $user->id,
                'role' => $role,
            ], [
                'id' => (string) Str::uuid(),
            ]);
        }
    }
}

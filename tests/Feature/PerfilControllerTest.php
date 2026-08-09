<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "O meu perfil" (`GET/PUT /perfil/me`) — sem gate de módulo, distinto do
 * módulo `users` genérico. Qualquer utilizador autenticado edita os
 * próprios dados, independentemente da matriz RBAC.
 */
class PerfilControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_devolve_o_perfil_do_utilizador_autenticado(): void
    {
        $user = User::factory()->create(['full_name' => 'Fulano de Tal']);

        $response = $this->actingAs($user)->getJson('/api/perfil/me');

        $response->assertOk()->assertJsonPath('fullName', 'Fulano de Tal');
    }

    public function test_update_altera_nome_telefone_e_avatar(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->putJson('/api/perfil/me', [
            'fullName' => 'Novo Nome',
            'phone' => '+244 900 000 000',
            'avatarUrl' => 'https://example.com/avatar.png',
        ]);

        $response->assertOk()
            ->assertJsonPath('fullName', 'Novo Nome')
            ->assertJsonPath('phone', '+244 900 000 000')
            ->assertJsonPath('avatarUrl', 'https://example.com/avatar.png');
    }

    public function test_update_nao_precisa_de_nenhum_modulo_rbac(): void
    {
        // Sem RolePermissionSeeder de propósito — sem papel/permissão
        // nenhuma, o próprio perfil continua editável.
        $user = User::factory()->create();

        $this->actingAs($user)
            ->putJson('/api/perfil/me', ['fullName' => 'Sem Papel Nenhum'])
            ->assertOk();
    }

    public function test_visitante_nao_autenticado_recebe_401(): void
    {
        $this->getJson('/api/perfil/me')->assertUnauthorized();
    }
}

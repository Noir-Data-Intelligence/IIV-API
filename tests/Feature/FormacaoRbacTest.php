<?php

namespace Tests\Feature;

use App\Enums\AppRole;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class FormacaoRbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function comPapel(AppRole $role): User
    {
        $user = User::factory()->create();
        UserRole::query()->create(['id' => (string) Str::uuid(), 'user_id' => $user->id, 'role' => $role]);

        return $user;
    }

    public function test_tecnico_tem_escrita_em_formacoes_sem_ter_escrita_em_rh(): void
    {
        // Regressao: a rota usava `module:rh,write`, mas a matriz RBAC trata
        // `formacoes` como modulo proprio — o Tecnico tem escrita em
        // `formacoes` mas nao em `rh`, pelo que a rota errada devolvia 403
        // apesar do botao aparecer no frontend (gated por `formacoes`).
        $tecnico = $this->comPapel(AppRole::Tecnico);

        $response = $this->actingAs($tecnico)->postJson('/api/formacoes', [
            'title' => 'Formação em Biossegurança',
            'startDate' => now()->addDay()->toDateString(),
        ]);

        $response->assertCreated();
    }
}

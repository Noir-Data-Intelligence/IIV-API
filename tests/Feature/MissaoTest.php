<?php

namespace Tests\Feature;

use App\Enums\AppRole;
use App\Models\Missao;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MissaoTest extends TestCase
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

    public function test_store_com_status_planeada_por_omissao_nao_falha_a_validacao(): void
    {
        // Regressao: o formulario de criacao envia sempre `status: 'planeada'`
        // por omissao, mas a regra de validacao (partilhada com o update) so
        // aceitava em_curso/concluida/cancelada, rejeitando toda a criacao
        // com 422 antes de chegar ao controller (que alias ignora o campo).
        $user = $this->comPapel(AppRole::Tecnico);

        $response = $this->actingAs($user)->postJson('/api/missoes', [
            'title' => 'Missão de Vacinação',
            'destination' => 'Huambo',
            'startDate' => now()->addDay()->toDateString(),
            'endDate' => now()->addDays(3)->toDateString(),
            'status' => 'planeada',
        ]);

        $response->assertCreated()->assertJsonPath('status', 'planeada');
    }

    public function test_update_ainda_rejeita_estados_reservados_ao_workflow(): void
    {
        $missao = Missao::factory()->create(['status' => 'em_curso']);
        $user = $this->comPapel(AppRole::Tecnico);

        $this->actingAs($user)->putJson("/api/missoes/{$missao->id}", ['status' => 'planeada'])
            ->assertStatus(422);
    }
}

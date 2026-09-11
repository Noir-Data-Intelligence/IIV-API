<?php

namespace Tests\Feature;

use App\Enums\AppRole;
use App\Models\Laboratorio;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LaboratorioTecnicoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        UserRole::query()->create(['id' => (string) Str::uuid(), 'user_id' => $user->id, 'role' => AppRole::Admin]);

        return $user;
    }

    private function directorLaboratorio(): User
    {
        $user = User::factory()->create();
        UserRole::query()->create(['id' => (string) Str::uuid(), 'user_id' => $user->id, 'role' => AppRole::DirectorLaboratorio]);

        return $user;
    }

    public function test_director_laboratorio_adiciona_tecnico_a_equipa(): void
    {
        $laboratorio = Laboratorio::factory()->create();
        $tecnico = User::factory()->create();

        $response = $this->actingAs($this->directorLaboratorio())
            ->postJson("/api/laboratorios/{$laboratorio->id}/tecnicos", [
                'userId' => $tecnico->id,
                'funcao' => 'Técnico de bancada',
            ]);

        $response->assertCreated()->assertJsonPath('userId', $tecnico->id);
        $this->assertDatabaseHas('laboratorio_tecnicos', [
            'laboratorio_id' => $laboratorio->id,
            'user_id' => $tecnico->id,
            'funcao' => 'Técnico de bancada',
        ]);
    }

    public function test_nao_duplica_tecnico_ja_na_equipa(): void
    {
        $laboratorio = Laboratorio::factory()->create();
        $tecnico = User::factory()->create();
        $laboratorio->tecnicos()->attach($tecnico->id, ['funcao' => 'X']);

        $this->actingAs($this->admin())->postJson("/api/laboratorios/{$laboratorio->id}/tecnicos", [
            'userId' => $tecnico->id,
        ])->assertCreated();

        $this->assertSame(
            1,
            \DB::table('laboratorio_tecnicos')
                ->where('laboratorio_id', $laboratorio->id)
                ->where('user_id', $tecnico->id)
                ->count(),
        );
    }

    public function test_lista_equipa_do_laboratorio(): void
    {
        $laboratorio = Laboratorio::factory()->create();
        $tecnico = User::factory()->create();
        $laboratorio->tecnicos()->attach($tecnico->id, ['funcao' => 'Técnico']);

        $this->actingAs($this->admin())->getJson("/api/laboratorios/{$laboratorio->id}/tecnicos")
            ->assertOk()
            ->assertJsonFragment(['userId' => $tecnico->id, 'funcao' => 'Técnico']);
    }

    public function test_remove_tecnico_da_equipa(): void
    {
        $laboratorio = Laboratorio::factory()->create();
        $tecnico = User::factory()->create();
        $laboratorio->tecnicos()->attach($tecnico->id);

        $this->actingAs($this->admin())
            ->deleteJson("/api/laboratorios/{$laboratorio->id}/tecnicos/{$tecnico->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('laboratorio_tecnicos', [
            'laboratorio_id' => $laboratorio->id,
            'user_id' => $tecnico->id,
        ]);
    }

    public function test_store_rejeita_utilizador_inexistente(): void
    {
        $laboratorio = Laboratorio::factory()->create();

        $this->actingAs($this->admin())->postJson("/api/laboratorios/{$laboratorio->id}/tecnicos", [
            'userId' => (string) Str::uuid(),
        ])->assertStatus(422);
    }

    public function test_tecnico_sem_permissao_de_escrita_nao_pode_adicionar(): void
    {
        $laboratorio = Laboratorio::factory()->create();
        $tecnico = User::factory()->create();
        UserRole::query()->create(['id' => (string) Str::uuid(), 'user_id' => $tecnico->id, 'role' => AppRole::Recepcionista]);

        $this->actingAs($tecnico)->postJson("/api/laboratorios/{$laboratorio->id}/tecnicos", [
            'userId' => $tecnico->id,
        ])->assertForbidden();
    }

    public function test_director_laboratorio_tem_permissao_de_leitura_sobre_utilizadores(): void
    {
        $this->actingAs($this->directorLaboratorio())->getJson('/api/users')->assertOk();
    }

    public function test_director_laboratorio_nao_tem_permissao_de_escrita_sobre_utilizadores(): void
    {
        $outro = User::factory()->create();

        $this->actingAs($this->directorLaboratorio())
            ->putJson("/api/users/{$outro->id}", ['fullName' => 'Novo Nome'])
            ->assertForbidden();
    }
}

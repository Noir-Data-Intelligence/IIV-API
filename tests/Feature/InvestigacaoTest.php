<?php

namespace Tests\Feature;

use App\Enums\AppRole;
use App\Models\Publication;
use App\Models\ResearchLine;
use App\Models\ResearchProject;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class InvestigacaoTest extends TestCase
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

    public function test_store_linha_projecto_e_publicacao(): void
    {
        $user = $this->comPapel(AppRole::Tecnico);

        $linha = $this->actingAs($user)->postJson('/api/investigacao/linhas', [
            'name' => 'Epidemiologia',
        ])->assertCreated()->json();

        $this->assertSame('activa', $linha['status']);

        $projecto = $this->actingAs($user)->postJson('/api/investigacao/projectos', [
            'lineId' => $linha['id'], 'title' => 'Vigilância de raiva bovina', 'fundingAmount' => 500000,
        ])->assertCreated()->json();

        $this->assertSame('proposto', $projecto['status']);
        $this->assertSame('AOA', $projecto['currency']);

        $publicacao = $this->actingAs($user)->postJson('/api/investigacao/publicacoes', [
            'projectId' => $projecto['id'], 'title' => 'Estudo de caso', 'authors' => ['Ana Silva', 'João Costa'],
        ])->assertCreated()->json();

        $this->assertSame('artigo', $publicacao['type']);
        $this->assertSame(['Ana Silva', 'João Costa'], $publicacao['authors']);
    }

    public function test_stats_calcula_projectos_activos_e_financiamento_total(): void
    {
        ResearchLine::factory()->count(2)->create();
        ResearchProject::factory()->create(['line_id' => null, 'status' => 'em_curso', 'funding_amount' => 100000]);
        ResearchProject::factory()->create(['line_id' => null, 'status' => 'aprovado', 'funding_amount' => 200000]);
        ResearchProject::factory()->create(['line_id' => null, 'status' => 'cancelado', 'funding_amount' => 999999]);
        ResearchProject::factory()->create(['line_id' => null, 'status' => 'proposto', 'funding_amount' => 50000]);
        Publication::factory()->count(3)->create(['project_id' => null]);
        $user = $this->comPapel(AppRole::Tecnico);

        $this->actingAs($user)->getJson('/api/investigacao/stats')->assertOk()
            ->assertJsonPath('totalLines', 2)
            ->assertJsonPath('activeProjects', 2)
            ->assertJsonPath('totalPublications', 3)
            ->assertJsonPath('totalFunding', 350000);
    }

    public function test_apagar_linha_desassocia_projectos_em_vez_de_arrastar(): void
    {
        $linha = ResearchLine::factory()->create();
        $projecto = ResearchProject::factory()->create(['line_id' => $linha->id]);
        $user = $this->comPapel(AppRole::Tecnico);

        $this->actingAs($user)->deleteJson("/api/investigacao/linhas/{$linha->id}")->assertNoContent();

        $this->assertDatabaseMissing('research_lines', ['id' => $linha->id]);
        $this->assertDatabaseHas('research_projects', ['id' => $projecto->id, 'line_id' => null]);
    }

    public function test_apagar_projecto_desassocia_publicacoes_em_vez_de_arrastar(): void
    {
        $projecto = ResearchProject::factory()->create();
        $publicacao = Publication::factory()->create(['project_id' => $projecto->id]);
        $user = $this->comPapel(AppRole::Tecnico);

        $this->actingAs($user)->deleteJson("/api/investigacao/projectos/{$projecto->id}")->assertNoContent();

        $this->assertDatabaseMissing('research_projects', ['id' => $projecto->id]);
        $this->assertDatabaseHas('publications', ['id' => $publicacao->id, 'project_id' => null]);
    }

    public function test_index_filtra_por_estado_e_linha(): void
    {
        $linha = ResearchLine::factory()->create();
        ResearchProject::factory()->create(['line_id' => $linha->id, 'status' => 'em_curso']);
        ResearchProject::factory()->create(['status' => 'proposto']);
        $user = $this->comPapel(AppRole::Tecnico);

        $this->actingAs($user)->getJson("/api/investigacao/projectos?line_id={$linha->id}")
            ->assertOk()->assertJsonPath('meta.total', 1);
    }

    public function test_store_sem_permissao_devolve_403(): void
    {
        $user = $this->comPapel(AppRole::Isv);

        $this->actingAs($user)->postJson('/api/investigacao/linhas', ['name' => 'X'])->assertForbidden();
    }
}

<?php

namespace Tests\Feature;

use App\Enums\AppRole;
use App\Models\Auditoria;
use App\Models\NaoConformidade;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NaoConformidadeTest extends TestCase
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

    public function test_index_filtra_por_severity_e_status(): void
    {
        NaoConformidade::factory()->create(['severity' => 'critica', 'status' => 'aberta']);
        NaoConformidade::factory()->create(['severity' => 'menor', 'status' => 'resolvida']);

        $user = $this->comPapel(AppRole::DirectorLaboratorio);

        $this->actingAs($user)->getJson('/api/nao-conformidades?severity=critica')->assertJsonPath('meta.total', 1);
        $this->actingAs($user)->getJson('/api/nao-conformidades?status=resolvida')->assertJsonPath('meta.total', 1);
    }

    public function test_store_resolve_audit_title(): void
    {
        $auditoria = Auditoria::factory()->create(['title' => 'Auditoria Anual 2026']);
        $user = $this->comPapel(AppRole::ResponsavelQualidade);

        $response = $this->actingAs($user)->postJson('/api/nao-conformidades', [
            'title' => 'Falha de rotulagem',
            'description' => 'Rótulos sem lote impresso.',
            'severity' => 'maior',
            'auditId' => $auditoria->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('auditTitle', 'Auditoria Anual 2026')
            ->assertJsonPath('status', 'aberta');
        $this->assertNull($response->json('resolvedAt'));
    }

    public function test_resolved_at_gravado_ao_resolver_e_limpo_ao_reabrir(): void
    {
        $nc = NaoConformidade::factory()->create(['status' => 'aberta']);
        $user = $this->comPapel(AppRole::ResponsavelQualidade);

        $resolved = $this->actingAs($user)->putJson("/api/nao-conformidades/{$nc->id}", [
            'title' => $nc->title, 'description' => $nc->description, 'severity' => $nc->severity, 'status' => 'resolvida',
        ]);
        $resolved->assertOk();
        $this->assertNotNull($resolved->json('resolvedAt'));

        $reopened = $this->actingAs($user)->putJson("/api/nao-conformidades/{$nc->id}", [
            'title' => $nc->title, 'description' => $nc->description, 'severity' => $nc->severity, 'status' => 'em_resolucao',
        ]);
        $reopened->assertOk();
        $this->assertNull($reopened->json('resolvedAt'));
    }

    public function test_store_sem_permissao_devolve_403(): void
    {
        $user = $this->comPapel(AppRole::Isv);

        $this->actingAs($user)->postJson('/api/nao-conformidades', [
            'title' => 'X', 'description' => 'Y', 'severity' => 'menor',
        ])->assertForbidden();
    }
}

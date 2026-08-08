<?php

namespace Tests\Feature;

use App\Enums\AppRole;
use App\Models\ProcessType;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProcessTypeTest extends TestCase
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

    public function test_index_filtra_active_only(): void
    {
        ProcessType::factory()->create(['is_active' => true]);
        ProcessType::factory()->create(['is_active' => false]);
        $user = $this->comPapel(AppRole::DirectorLaboratorio);

        $this->actingAs($user)->getJson('/api/processos-tipos?active_only=true')->assertJsonPath('meta.total', 1);
        $this->actingAs($user)->getJson('/api/processos-tipos')->assertJsonPath('meta.total', 2);
    }

    public function test_store_etapas_e_reordenar(): void
    {
        $type = ProcessType::factory()->create();
        $user = $this->comPapel(AppRole::DirectorLaboratorio);

        $step1 = $this->actingAs($user)->postJson("/api/processos-tipos/{$type->id}/etapas", [
            'orderIndex' => 1, 'name' => 'Submissão',
        ])->assertCreated()->json('id');

        $step2 = $this->actingAs($user)->postJson("/api/processos-tipos/{$type->id}/etapas", [
            'orderIndex' => 2, 'name' => 'Aprovação',
        ])->assertCreated()->json('id');

        $reordered = $this->actingAs($user)->postJson("/api/processos-tipos/{$type->id}/etapas/reordenar", [
            'orderedIds' => [$step2, $step1],
        ]);

        $reordered->assertOk();
        $this->assertSame($step2, $reordered->json('0.id'));
        $this->assertSame(1, $reordered->json('0.orderIndex'));
    }

    public function test_store_sem_permissao_devolve_403(): void
    {
        $user = $this->comPapel(AppRole::Isv);

        $this->actingAs($user)->postJson('/api/processos-tipos', ['name' => 'X'])->assertForbidden();
    }
}

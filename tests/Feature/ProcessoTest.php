<?php

namespace Tests\Feature;

use App\Enums\AppRole;
use App\Models\Documento;
use App\Models\Processo;
use App\Models\ProcessType;
use App\Models\ProcessTypeStep;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProcessoTest extends TestCase
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

    /** Tipo com 2 etapas-padrão (Submissão, Aprovação). */
    private function tipoComEtapas(): ProcessType
    {
        $type = ProcessType::factory()->create();
        ProcessTypeStep::query()->create(['id' => (string) Str::uuid(), 'process_type_id' => $type->id, 'order_index' => 1, 'name' => 'Submissão']);
        ProcessTypeStep::query()->create(['id' => (string) Str::uuid(), 'process_type_id' => $type->id, 'order_index' => 2, 'name' => 'Aprovação']);

        return $type;
    }

    public function test_store_instancia_etapas_e_activa_a_primeira(): void
    {
        $type = $this->tipoComEtapas();
        $user = $this->comPapel(AppRole::DirectorLaboratorio);

        $response = $this->actingAs($user)->postJson('/api/processos', [
            'typeId' => $type->id,
            'title' => 'Pedido de Compra de Reagentes',
            'priority' => 'normal',
            'requesterId' => $user->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('status', 'em_curso')
            ->assertJsonPath('title', 'Pedido de Compra de Reagentes');
        $this->assertNotNull($response->json('code'));
        $this->assertStringStartsWith('PRC-'.now()->year, $response->json('code'));
        $this->assertNotNull($response->json('currentStepId'));

        $steps = $this->actingAs($user)->getJson("/api/processos/{$response->json('id')}/etapas");
        $steps->assertOk();
        $this->assertCount(2, $steps->json());
        $this->assertSame('em_curso', $steps->json('0.status'));
        $this->assertSame('pendente', $steps->json('1.status'));

        $events = $this->actingAs($user)->getJson("/api/processos/{$response->json('id')}/eventos");
        $this->assertSame('aberto', $events->json('0.eventType'));
    }

    public function test_avancar_conclui_etapa_e_activa_a_proxima(): void
    {
        $type = $this->tipoComEtapas();
        $user = $this->comPapel(AppRole::DirectorLaboratorio);
        $processoId = $this->actingAs($user)->postJson('/api/processos', [
            'typeId' => $type->id, 'title' => 'X', 'priority' => 'normal', 'requesterId' => $user->id,
        ])->json('id');

        $response = $this->actingAs($user)->postJson("/api/processos/{$processoId}/avancar", ['notes' => 'Ok']);

        $response->assertOk()->assertJsonPath('status', 'em_curso');
        $steps = $this->actingAs($user)->getJson("/api/processos/{$processoId}/etapas")->json();
        $this->assertSame('concluida', $steps[0]['status']);
        $this->assertSame('em_curso', $steps[1]['status']);
    }

    public function test_avancar_na_ultima_etapa_fecha_o_processo(): void
    {
        $type = $this->tipoComEtapas();
        $user = $this->comPapel(AppRole::DirectorLaboratorio);
        $processoId = $this->actingAs($user)->postJson('/api/processos', [
            'typeId' => $type->id, 'title' => 'X', 'priority' => 'normal', 'requesterId' => $user->id,
        ])->json('id');

        $this->actingAs($user)->postJson("/api/processos/{$processoId}/avancar")->assertOk();
        $response = $this->actingAs($user)->postJson("/api/processos/{$processoId}/avancar");

        $response->assertOk()
            ->assertJsonPath('status', 'concluido')
            ->assertJsonPath('currentStepId', null);
        $this->assertNotNull($response->json('closedAt'));
    }

    public function test_devolver_reactiva_a_etapa_anterior(): void
    {
        $type = $this->tipoComEtapas();
        $user = $this->comPapel(AppRole::DirectorLaboratorio);
        $processoId = $this->actingAs($user)->postJson('/api/processos', [
            'typeId' => $type->id, 'title' => 'X', 'priority' => 'normal', 'requesterId' => $user->id,
        ])->json('id');

        $this->actingAs($user)->postJson("/api/processos/{$processoId}/avancar")->assertOk();
        $response = $this->actingAs($user)->postJson("/api/processos/{$processoId}/devolver", ['notes' => 'Falta assinatura']);

        $response->assertOk()->assertJsonPath('status', 'em_curso');
        $steps = $this->actingAs($user)->getJson("/api/processos/{$processoId}/etapas")->json();
        $this->assertSame('em_curso', $steps[0]['status']);
        $this->assertSame('devolvida', $steps[1]['status']);
    }

    public function test_devolver_sem_etapa_anterior_devolve_422(): void
    {
        $type = $this->tipoComEtapas();
        $user = $this->comPapel(AppRole::DirectorLaboratorio);
        $processoId = $this->actingAs($user)->postJson('/api/processos', [
            'typeId' => $type->id, 'title' => 'X', 'priority' => 'normal', 'requesterId' => $user->id,
        ])->json('id');

        $this->actingAs($user)->postJson("/api/processos/{$processoId}/devolver")->assertStatus(422);
    }

    public function test_cancelar_fecha_o_processo_com_justificacao(): void
    {
        $processo = Processo::factory()->create();
        $user = $this->comPapel(AppRole::DirectorLaboratorio);

        $response = $this->actingAs($user)->postJson("/api/processos/{$processo->id}/cancelar", ['notes' => 'Duplicado']);

        $response->assertOk()->assertJsonPath('status', 'cancelado');
        $events = $this->actingAs($user)->getJson("/api/processos/{$processo->id}/eventos");
        $this->assertSame('cancelado', $events->json('0.eventType'));
        $this->assertSame('Duplicado', $events->json('0.payload.reason'));
    }

    public function test_stats_conta_por_estado(): void
    {
        Processo::factory()->create(['status' => 'aberto']);
        Processo::factory()->count(2)->create(['status' => 'em_curso']);
        Processo::factory()->create(['status' => 'concluido']);
        $user = $this->comPapel(AppRole::DirectorLaboratorio);

        $response = $this->actingAs($user)->getJson('/api/processos/stats');

        $response->assertOk()
            ->assertJsonPath('abertos', 1)
            ->assertJsonPath('emCurso', 2)
            ->assertJsonPath('concluidos', 1);
    }

    public function test_anexo_com_documento_embebe_resumo(): void
    {
        $processo = Processo::factory()->create();
        $documento = Documento::factory()->create(['title' => 'Ficha Técnica']);
        $user = $this->comPapel(AppRole::DirectorLaboratorio);

        $response = $this->actingAs($user)->postJson("/api/processos/{$processo->id}/anexos", [
            'label' => 'Ficha', 'documentId' => $documento->id,
        ]);

        $response->assertCreated()->assertJsonPath('document.title', 'Ficha Técnica');
    }

    public function test_update_funciona_sem_etapa_activa(): void
    {
        // Regressao: o frontend escondia Editar fora do estado "em_curso",
        // mas o backend sempre aceitou update em qualquer estado nao-terminal.
        $processo = Processo::factory()->create(['status' => 'aberto', 'title' => 'Original']);
        $user = $this->comPapel(AppRole::DirectorLaboratorio);

        $response = $this->actingAs($user)->putJson("/api/processos/{$processo->id}", [
            'title' => 'Título Corrigido',
            'priority' => 'alta',
        ]);

        $response->assertOk()->assertJsonPath('title', 'Título Corrigido')->assertJsonPath('priority', 'alta');
    }

    public function test_destroy_remove_o_processo(): void
    {
        $processo = Processo::factory()->create();
        $user = $this->comPapel(AppRole::DirectorLaboratorio);

        $this->actingAs($user)->deleteJson("/api/processos/{$processo->id}")->assertNoContent();
        $this->assertDatabaseMissing('processos', ['id' => $processo->id]);
    }

    public function test_store_sem_permissao_devolve_403(): void
    {
        $type = $this->tipoComEtapas();
        $user = $this->comPapel(AppRole::Isv);

        $this->actingAs($user)->postJson('/api/processos', [
            'typeId' => $type->id, 'title' => 'X', 'priority' => 'normal', 'requesterId' => $user->id,
        ])->assertForbidden();
    }
}

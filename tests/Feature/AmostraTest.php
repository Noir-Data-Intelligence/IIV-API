<?php

namespace Tests\Feature;

use App\Enums\AmostraStatus;
use App\Enums\AppRole;
use App\Models\Amostra;
use App\Models\CriterioRejeicao;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AmostraTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function tecnico(): User
    {
        $user = User::factory()->create();
        UserRole::query()->create(['id' => (string) Str::uuid(), 'user_id' => $user->id, 'role' => AppRole::Tecnico]);

        return $user;
    }

    public function test_aceitar_amostra_abre_boletim_interno(): void
    {
        $amostra = Amostra::factory()->create();

        $response = $this->actingAs($this->tecnico())->postJson("/api/amostras/{$amostra->id}/aceitar");

        $response->assertOk()->assertJsonPath('status', AmostraStatus::Aceite->value);
        $this->assertNotNull($response->json('boletimInterno.id'));
        $this->assertNotNull($response->json('boletimInterno.numeroAnalise'));
        $this->assertDatabaseCount('boletins_internos', 1);
    }

    public function test_aceitar_amostra_ja_aceite_falha(): void
    {
        $amostra = Amostra::factory()->create(['status' => AmostraStatus::Aceite]);

        $this->actingAs($this->tecnico())->postJson("/api/amostras/{$amostra->id}/aceitar")
            ->assertStatus(422);
    }

    public function test_rejeitar_amostra_exige_criterio_e_assinatura(): void
    {
        $amostra = Amostra::factory()->create();

        $this->actingAs($this->tecnico())->postJson("/api/amostras/{$amostra->id}/rejeitar", [])
            ->assertStatus(422);
    }

    public function test_rejeitar_amostra_regista_termo_de_rejeicao(): void
    {
        $amostra = Amostra::factory()->create();
        $criterio = CriterioRejeicao::factory()->create();

        $response = $this->actingAs($this->tecnico())->postJson("/api/amostras/{$amostra->id}/rejeitar", [
            'criterioRejeicaoId' => $criterio->id,
            'assinaturaResponsavel' => 'assinatura-tecnico',
            'detalhe' => 'Amostra hemolisada',
        ]);

        $response->assertOk()->assertJsonPath('status', AmostraStatus::Rejeitada->value);
        $this->assertDatabaseHas('amostra_rejeicoes', [
            'amostra_id' => $amostra->id,
            'criterio_rejeicao_id' => $criterio->id,
        ]);
    }

    public function test_nao_e_possivel_aceitar_uma_amostra_ja_rejeitada(): void
    {
        $amostra = Amostra::factory()->create(['status' => AmostraStatus::Rejeitada]);

        $this->actingAs($this->tecnico())->postJson("/api/amostras/{$amostra->id}/aceitar")
            ->assertStatus(422);
    }
}

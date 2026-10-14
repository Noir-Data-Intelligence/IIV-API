<?php

namespace Tests\Feature;

use App\Enums\AppRole;
use App\Enums\BoletimInternoStatus;
use App\Models\Amostra;
use App\Models\BoletimInterno;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Rotas LEGADAS `/boletins`. As transições antigas (resultado -> validar ->
 * aprovado automático -> comunicar pelo técnico) eram o DEF-002/003 e foram
 * retiradas (410 Gone). As regras de validação (validadores distintos,
 * lista nomeada AB, gate BR Salmonela=0, executor nunca valida) estão agora
 * cobertas em LabAreaWorkflowTest e LabFluxoCompletoTest.
 */
class BoletimTest extends TestCase
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

    private function boletim(): BoletimInterno
    {
        return BoletimInterno::factory()->create(['amostra_id' => Amostra::factory()->create()->id]);
    }

    public function test_transicoes_legadas_foram_retiradas(): void
    {
        $boletim = $this->boletim();
        $admin = $this->comPapel(AppRole::Admin);

        $this->actingAs($admin)->putJson("/api/boletins/{$boletim->id}/resultado", ['resultado' => ['ph' => 7]])->assertStatus(410);
        $this->actingAs($admin)->postJson("/api/boletins/{$boletim->id}/validar")->assertStatus(410);
        $this->actingAs($admin)->postJson("/api/boletins/{$boletim->id}/comunicar")->assertStatus(410);
        $this->assertSame(BoletimInternoStatus::EmAnalise, $boletim->refresh()->status);
    }

    public function test_tecnico_nao_comunica_resultados(): void
    {
        $boletim = $this->boletim();

        $this->actingAs($this->comPapel(AppRole::Tecnico))->postJson("/api/boletins/{$boletim->id}/comunicar")->assertForbidden();
    }

    public function test_leitura_legada_continua_a_funcionar(): void
    {
        $boletim = $this->boletim();
        $user = $this->comPapel(AppRole::Recepcionista);

        $this->actingAs($user)->getJson('/api/boletins')->assertOk()->assertJsonCount(1, 'data');
        $this->actingAs($user)->getJson("/api/boletins/{$boletim->id}")->assertOk()->assertJsonPath('status', 'em_analise');
    }
}

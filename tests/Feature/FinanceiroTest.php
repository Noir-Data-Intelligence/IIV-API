<?php

namespace Tests\Feature;

use App\Enums\AppRole;
use App\Models\Conta;
use App\Models\Lancamento;
use App\Models\Orcamento;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class FinanceiroTest extends TestCase
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

    public function test_index_contas_pagina_e_filtra(): void
    {
        Conta::factory()->create(['code' => '1.1', 'type' => 'receita']);
        Conta::factory()->create(['code' => '2.1', 'type' => 'despesa']);
        $user = $this->comPapel(AppRole::GestorFinanceiro);

        $this->actingAs($user)->getJson('/api/contas?type=receita')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.code', '1.1');
    }

    public function test_store_conta_e_lancamento_e_orcamento(): void
    {
        $user = $this->comPapel(AppRole::GestorFinanceiro);

        $conta = $this->actingAs($user)->postJson('/api/contas', [
            'code' => '3.1', 'name' => 'Receitas de análises', 'type' => 'receita',
        ])->assertCreated()->json();

        $this->assertTrue($conta['isActive']);

        $lancamento = $this->actingAs($user)->postJson('/api/lancamentos', [
            'accountId' => $conta['id'],
            'type' => 'receita',
            'amount' => 15000.50,
            'transactionDate' => '2026-01-15',
            'description' => 'Pagamento de análise',
        ])->assertCreated()->json();

        $this->assertSame('AOA', $lancamento['currency']);
        $this->assertSame('pago', $lancamento['status']);
        $this->assertSame(15000.5, $lancamento['amount']);

        $orcamento = $this->actingAs($user)->postJson('/api/orcamentos', [
            'year' => 2026,
            'accountId' => $conta['id'],
            'plannedAmount' => 500000,
        ])->assertCreated()->json();

        $this->assertSame(2026, $orcamento['year']);
    }

    public function test_update_conta(): void
    {
        $conta = Conta::factory()->create(['name' => 'Original']);
        $user = $this->comPapel(AppRole::GestorFinanceiro);

        $this->actingAs($user)->putJson("/api/contas/{$conta->id}", [
            'code' => $conta->code, 'name' => 'Actualizada', 'type' => $conta->type, 'isActive' => false,
        ])->assertOk()->assertJsonPath('name', 'Actualizada')->assertJsonPath('isActive', false);
    }

    public function test_destroy_conta_arrasta_lancamentos_e_orcamentos_em_cascata(): void
    {
        $conta = Conta::factory()->create();
        $lancamento = Lancamento::factory()->create(['account_id' => $conta->id]);
        $orcamento = Orcamento::factory()->create(['account_id' => $conta->id]);
        $user = $this->comPapel(AppRole::GestorFinanceiro);

        $this->actingAs($user)->deleteJson("/api/contas/{$conta->id}")->assertNoContent();

        $this->assertDatabaseMissing('contas', ['id' => $conta->id]);
        $this->assertDatabaseMissing('lancamentos', ['id' => $lancamento->id]);
        $this->assertDatabaseMissing('orcamentos', ['id' => $orcamento->id]);
    }

    public function test_index_lancamentos_filtra_por_conta_e_estado(): void
    {
        $contaA = Conta::factory()->create();
        $contaB = Conta::factory()->create();
        Lancamento::factory()->create(['account_id' => $contaA->id, 'status' => 'pago']);
        Lancamento::factory()->create(['account_id' => $contaA->id, 'status' => 'pendente']);
        Lancamento::factory()->create(['account_id' => $contaB->id, 'status' => 'pago']);
        $user = $this->comPapel(AppRole::GestorFinanceiro);

        $this->actingAs($user)
            ->getJson("/api/lancamentos?account_id={$contaA->id}&status=pago")
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_store_sem_permissao_devolve_403(): void
    {
        $user = $this->comPapel(AppRole::Isv);

        $this->actingAs($user)->postJson('/api/contas', [
            'code' => '9.9', 'name' => 'X', 'type' => 'despesa',
        ])->assertForbidden();
    }

    public function test_store_lancamento_sem_descricao_devolve_422(): void
    {
        $conta = Conta::factory()->create();
        $user = $this->comPapel(AppRole::GestorFinanceiro);

        $this->actingAs($user)->postJson('/api/lancamentos', [
            'accountId' => $conta->id,
            'type' => 'receita',
            'amount' => 1000,
            'transactionDate' => '2026-01-15',
        ])->assertUnprocessable()->assertJsonValidationErrors(['description']);
    }
}

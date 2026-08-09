<?php

namespace Tests\Feature;

use App\Enums\AppRole;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Ciclo de vida de autenticacao — ver PLANO-BACKEND-LARAVEL.txt seccao 12 e
 * SIG-IIV-MEMORIA-PROJETO.md seccao 15.
 */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Sanctum só arranca a sessão (StartSession) em pedidos que
        // reconhece como vindos do frontend (Referer/Origin a bater com
        // SANCTUM_STATEFUL_DOMAINS) — tal como um browser real enviaria
        // sempre em pedidos cross-origin. Sem isto, `$request->session()`
        // no AuthController rebenta em qualquer pedido HTTP real de
        // login/registo/logout nos testes (ver EnsureFrontendRequestsAreStateful).
        $this->withHeader('Referer', config('app.frontend_url'));
    }

    private function makeUser(string $password = 'password123'): User
    {
        $user = User::query()->create([
            'id' => (string) Str::uuid(),
            'full_name' => 'Utilizador Teste',
            'email' => 'teste@iiv.demo',
            'password' => Hash::make($password),
        ]);

        UserRole::query()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'role' => AppRole::Tecnico,
        ]);

        return $user;
    }

    public function test_login_com_credenciais_validas_autentica_a_sessao(): void
    {
        $this->makeUser('senha-correcta');

        $response = $this->postJson('/api/login', [
            'email' => 'teste@iiv.demo',
            'password' => 'senha-correcta',
        ]);

        $response->assertNoContent();
        $this->assertAuthenticated();
    }

    public function test_login_sem_referer_origin_devolve_422_em_vez_de_rebentar(): void
    {
        // Regressão: sem Referer/Origin a bater com SANCTUM_STATEFUL_DOMAINS
        // (cliente mal configurado, health-checker, proxy que remove o
        // header), `EnsureFrontendRequestsAreStateful` não arranca a sessão e
        // `$request->session()` rebentava com RuntimeException (500) em vez
        // de um erro diagnosticável. Este teste ignora deliberadamente o
        // `withHeader('Referer', ...)` do setUp() para reproduzir o caso.
        $this->makeUser('senha-correcta');

        $response = $this->withHeaders(['Referer' => ''])->postJson('/api/login', [
            'email' => 'teste@iiv.demo',
            'password' => 'senha-correcta',
        ]);

        $response->assertStatus(422);
        $this->assertGuest();
    }

    public function test_login_com_credenciais_invalidas_devolve_422(): void
    {
        $this->makeUser('senha-correcta');

        $response = $this->postJson('/api/login', [
            'email' => 'teste@iiv.demo',
            'password' => 'senha-errada',
        ]);

        $response->assertStatus(422);
        $this->assertGuest();
    }

    public function test_get_user_sem_sessao_devolve_401(): void
    {
        $this->getJson('/api/user')->assertStatus(401);
    }

    public function test_get_user_com_sessao_devolve_o_userdto_com_roles(): void
    {
        $user = $this->makeUser();

        $response = $this->actingAs($user)->getJson('/api/user');

        $response->assertOk()->assertJsonPath('email', 'teste@iiv.demo')
            ->assertJsonPath('roles', ['tecnico']);
    }

    public function test_registo_cria_conta_com_papel_recepcionista_por_omissao(): void
    {
        $response = $this->postJson('/api/register', [
            'fullName' => 'Novo Utilizador',
            'email' => 'novo@iiv.demo',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()->assertJsonPath('roles', ['recepcionista']);
        $this->assertDatabaseHas('users', ['email' => 'novo@iiv.demo']);
    }

    public function test_registo_com_email_duplicado_devolve_422(): void
    {
        $this->makeUser();

        $response = $this->postJson('/api/register', [
            'fullName' => 'Outro',
            'email' => 'teste@iiv.demo',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_forgot_password_devolve_sempre_204_mesmo_com_email_inexistente(): void
    {
        $this->postJson('/api/forgot-password', ['email' => 'nao-existe@iiv.demo'])
            ->assertNoContent();

        $this->makeUser();
        $this->postJson('/api/forgot-password', ['email' => 'teste@iiv.demo'])
            ->assertNoContent();
    }

    public function test_reset_password_com_token_invalido_devolve_422(): void
    {
        $this->makeUser();

        $response = $this->postJson('/api/reset-password', [
            'token' => 'token-invalido',
            'email' => 'teste@iiv.demo',
            'password' => 'nova-senha123',
            'password_confirmation' => 'nova-senha123',
        ]);

        $response->assertStatus(422);
    }

    public function test_reset_password_com_token_valido_actualiza_a_password(): void
    {
        $user = $this->makeUser('senha-antiga');
        $token = Password::createToken($user);

        $response = $this->postJson('/api/reset-password', [
            'token' => $token,
            'email' => 'teste@iiv.demo',
            'password' => 'senha-nova123',
            'password_confirmation' => 'senha-nova123',
        ]);

        $response->assertNoContent();

        $this->postJson('/api/login', ['email' => 'teste@iiv.demo', 'password' => 'senha-nova123'])
            ->assertNoContent();
    }

    public function test_change_password_autenticado_actualiza_a_propria_password(): void
    {
        $user = $this->makeUser('senha-actual');

        $this->actingAs($user)
            ->patchJson('/api/user/password', ['password' => 'senha-seguinte123'])
            ->assertNoContent();

        $this->assertTrue(Hash::check('senha-seguinte123', $user->fresh()->password));
    }

    public function test_logout_termina_a_sessao(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user);
        $this->assertAuthenticated();

        $this->postJson('/api/logout')->assertNoContent();
    }
}

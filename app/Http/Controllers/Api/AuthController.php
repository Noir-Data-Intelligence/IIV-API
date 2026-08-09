<?php

namespace App\Http\Controllers\Api;

use App\Enums\AppRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Contrato de autenticacao — ver SIG-IIV-MEMORIA-PROJETO.md seccao 15 e
 * PLANO-BACKEND-LARAVEL.txt seccao 2.2. Sanctum SPA cookie-based: as rotas
 * de login/register/logout NAO devolvem token nenhum, o cookie de sessao
 * viaja sozinho (`withCredentials: true` no frontend). O frontend real
 * (`Front-end/src/services/api/auth.ts`) já fala este contrato — chama
 * `GET /sanctum/csrf-cookie` antes do login e lê o utilizador via
 * `GET /user` depois, exactamente como aqui.
 */
class AuthController extends Controller
{
    public function login(LoginRequest $request): Response
    {
        // Sem sessão disponível, o pedido não foi reconhecido como "stateful"
        // por `EnsureFrontendRequestsAreStateful` (Origin/Referer fora de
        // `SANCTUM_STATEFUL_DOMAINS`, ou cliente que não envia nenhum dos
        // dois) — sem esta guarda, `$request->session()` abaixo rebenta com
        // RuntimeException (500) em vez de um erro diagnosticável.
        if (! $request->hasSession()) {
            throw ValidationException::withMessages([
                'email' => ['Não foi possível estabelecer sessão — confirme SANCTUM_STATEFUL_DOMAINS/FRONTEND_URL.'],
            ]);
        }

        $credentials = $request->validated();

        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais fornecidas não correspondem aos nossos registos.'],
            ]);
        }

        $request->session()->regenerate();

        return response()->noContent();
    }

    public function logout(Request $request): Response
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        if (! $request->hasSession()) {
            throw ValidationException::withMessages([
                'email' => ['Não foi possível estabelecer sessão — confirme SANCTUM_STATEFUL_DOMAINS/FRONTEND_URL.'],
            ]);
        }

        $data = $request->validated();

        $user = User::query()->create([
            'id' => (string) Str::uuid(),
            'full_name' => $data['fullName'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        UserRole::query()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'role' => AppRole::Recepcionista,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return (new UserResource($user->load('userRoles', 'departments')))
            ->response()
            ->setStatusCode(201);
    }

    public function forgotPassword(ForgotPasswordRequest $request): Response
    {
        // Nunca revelar se o email existe — devolve sempre 204 (ver RN de
        // seguranca no PLANO-BACKEND-LARAVEL.txt seccao 12/testes).
        Password::sendResetLink($request->only('email'));

        return response()->noContent();
    }

    public function resetPassword(ResetPasswordRequest $request): Response
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'token' => [__($status)],
            ]);
        }

        return response()->noContent();
    }

    public function changePassword(ChangePasswordRequest $request): Response
    {
        $request->user()->forceFill([
            'password' => Hash::make($request->validated()['password']),
        ])->save();

        return response()->noContent();
    }

    public function user(Request $request): UserResource
    {
        return new UserResource($request->user()->load('userRoles', 'departments'));
    }
}

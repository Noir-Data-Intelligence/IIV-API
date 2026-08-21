<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Sem isto, /login|/register|/forgot-password|/reset-password não
        // tinham NENHUM rate limit (auditoria de segurança, 2026-08-09) —
        // força bruta e password spraying sem custo nenhum para o atacante.
        // Par de limites: estreito por credencial (não deixa adivinhar a
        // password de uma conta específica) + largo por IP (não bloqueia
        // uma instituição inteira atrás de um único NAT).
        RateLimiter::for('auth', function (Request $request) {
            $key = mb_strtolower((string) $request->input('email', '')).'|'.$request->ip();

            return [
                Limit::perMinute(5)->by($key),
                Limit::perMinute(20)->by($request->ip()),
            ];
        });

        // Tecto global (`$middleware->throttleApi()` em bootstrap/app.php só
        // escolhe o NOME do limiter — tem de ser definido aqui, senão toda a
        // API rebenta com MissingRateLimiterException). Por utilizador
        // autenticado quando possível, por IP para pedidos anónimos (rotas
        // públicas de conteúdo).
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });
        // O frontend le sempre `response.data` como o DTO em si (ver
        // services/api/client.ts) — nunca um envelope `{data: ...}`. Sem
        // isto, todo o JsonResource singular ficaria automaticamente
        // embrulhado (comportamento por omissao do Laravel), quebrando o
        // contrato em todos os módulos excepto login/register (excepção já
        // documentada em AuthController, deferida para a Onda 12/cutover).
        JsonResource::withoutWrapping();

        // O link do email de recuperacao de senha aponta para a rota do
        // FRONTEND (RedefinirSenha.tsx le token/email da querystring), nao
        // para uma rota do backend — mesmo padrao do mock
        // (mocks/handlers/auth.ts, ver SIG-IIV-MEMORIA-PROJETO.md seccao 15).
        ResetPassword::createUrlUsing(function ($notifiable, string $token) {
            $frontendUrl = rtrim(config('app.frontend_url'), '/');

            return sprintf(
                '%s/redefinir-senha?token=%s&email=%s',
                $frontendUrl,
                $token,
                urlencode($notifiable->getEmailForPasswordReset())
            );
        });

        // Fora de `local`, o Scramble (docs Swagger em /docs/api) exige este
        // Gate — sem ele fica sempre 403 (RestrictedDocsAccess do pacote).
        // Só admin: o schema completo da API é informação sensível para um
        // atacante (rotas, regras de validação, estrutura de dados), mesma
        // postura das outras correcções de segurança desta sessão.
        Gate::define('viewApiDocs', fn (User $user) => $user->isAdmin());
    }
}

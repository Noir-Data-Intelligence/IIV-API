<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
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
    }
}

<?php

namespace App\Support\Scramble;

use Dedoc\Scramble\SecurityDocumentation\MiddlewareAuthSecurityStrategy;
use Dedoc\Scramble\Support\Generator\SecurityScheme;

/**
 * `config/scramble.php` tem de ficar `php artisan config:cache`-safe (sem
 * objectos/closures no array devolvido — `var_export()` rebenta em produção,
 * `SecuritySchemes\ApiKeySecurityScheme::__set_state()` não existe).
 * `MiddlewareAuthSecurityStrategy` aceita o `SecurityScheme` via construtor,
 * mas o exemplo do próprio pacote instancia-o directamente na config —
 * subclasse aqui para a config só guardar um class-string (string é sempre
 * cache-safe), o objecto só é construído quando o Scramble resolve esta
 * classe via `app()`, nunca durante o load/cache da config.
 */
class SanctumCookieSecurityStrategy extends MiddlewareAuthSecurityStrategy
{
    public function __construct()
    {
        parent::__construct(
            middleware: ['auth', 'auth:*'],
            scheme: SecurityScheme::apiKey('cookie', 'laravel_session')
                ->setDescription('Sessão Sanctum SPA (cookie httpOnly) — ver descrição da API para o fluxo completo com XSRF-TOKEN.'),
        );
    }
}

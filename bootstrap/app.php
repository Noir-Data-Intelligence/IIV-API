<?php

use App\Http\Middleware\EnsureModulePermission;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        // Atrás do Traefik (TLS termina lá, o container só vê HTTP simples
        // do proxy) — sem isto, `Request::ip()` no trilho de auditoria
        // (LogsAuditTrail) grava sempre o IP do Traefik, igual para todos
        // os utilizadores, e o esquema fica sempre "http" mesmo em produção
        // (auditoria de segurança, 2026-08-09). Confiar em '*' é aceitável
        // aqui porque só o Traefik consegue falar directamente com este
        // container (rede Docker interna, porta não publicada no host).
        $middleware->trustProxies(at: '*', headers: SymfonyRequest::HEADER_X_FORWARDED_FOR
            | SymfonyRequest::HEADER_X_FORWARDED_HOST
            | SymfonyRequest::HEADER_X_FORWARDED_PORT
            | SymfonyRequest::HEADER_X_FORWARDED_PROTO);
        // Tecto global de rate limit em toda a API (limiter `api` por
        // omissão, 60/min por utilizador autenticado ou por IP) — sem
        // isto, `/login`/`/register` etc. não tinham NENHUM rate limit
        // antes do `throttle:auth` explícito nessas rotas (ver routes/api.php),
        // e listagens paginadas (per_page até 2000) ficavam abertas a
        // table-scan repetido sem custo (auditoria de segurança, 2026-08-09).
        $middleware->throttleApi();

        $middleware->alias([
            'module' => EnsureModulePermission::class,
        ]);

        // Esta aplicação não tem rota `login` (SPA cookie-based, servida pelo
        // Vite, não por Blade) — `withRouting()` acima já registou um
        // `Authenticate::redirectUsing()` por omissão que chama `route('login')`
        // sempre que o pedido não pede JSON explicitamente (ex.: curl simples,
        // health checks), rebentando com RouteNotFoundException (500) em vez do
        // 401 esperado. Sobrepor aqui (depois de `withRouting()`, para não ser
        // sobrescrito) garante que nunca há redirecionamento — só JSON 401.
        Authenticate::redirectUsing(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Rede de segurança: garante 401 JSON mesmo que algum caminho ainda
        // devolva um `redirectTo` não nulo (ver `Authenticate::redirectUsing`
        // acima, que já cobre o caso principal).
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            return response()->json(['message' => 'Não autenticado.'], 401);
        });
    })->create();

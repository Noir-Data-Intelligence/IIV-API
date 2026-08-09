<?php

use App\Http\Middleware\EnsureModulePermission;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();

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

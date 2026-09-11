<?php

use App\Http\Middleware\EnsureAccountIsNotBlocked;
use App\Http\Middleware\EnsureIdempotencyKey;
use App\Http\Middleware\EnsureValidSessionToken;
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
        $middleware->alias([
            'session.token' => EnsureValidSessionToken::class,
            'idempotency' => EnsureIdempotencyKey::class,
            'account.notBlocked' => EnsureAccountIsNotBlocked::class,
        ]);

        // Aucune route nommée `login` n'existe (seule
        // `filament.admin.auth.login` existe, gérée par son propre panel) —
        // sans ça, un appel API sans jeton valide plante en 500
        // (RouteNotFoundException) au lieu d'un 401 JSON propre.
        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

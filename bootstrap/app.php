<?php

use App\Http\Middleware\EnsureAccountIsNotBlocked;
use App\Http\Middleware\EnsureFeatureEnabled;
use App\Http\Middleware\EnsureIdempotencyKey;
use App\Http\Middleware\EnsureNotInMaintenance;
use App\Http\Middleware\EnsureValidSessionToken;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        // Voir FinalizeWavePayouts : l'API Payout de Wave n'a pas de
        // webhook documenté, contrairement à Checkout.
        $schedule->command('wave:finalize-payouts')->everyFiveMinutes();

        // Sauvegardes : voir config/backup.php et VerifyBackupRestore.
        $schedule->command('backup:clean')->dailyAt('01:00');
        $schedule->command('backup:run')->dailyAt('01:30');
        $schedule->command('backup:monitor')->dailyAt('06:00');
        $schedule->command('backup:verify-restore')->weeklyOn(0, '05:00');
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'session.token' => EnsureValidSessionToken::class,
            'idempotency' => EnsureIdempotencyKey::class,
            'account.notBlocked' => EnsureAccountIsNotBlocked::class,
            'feature' => EnsureFeatureEnabled::class,
            'maintenance' => EnsureNotInMaintenance::class,
        ]);

        // Limiteur `api` défini dans AppServiceProvider (120/min par compte
        // ou par IP) ; les webhooks en sont exclus dans routes/api.php.
        $middleware->throttleApi();

        // Aucune route nommée `login` n'existe (seule
        // `filament.admin.auth.login` existe, gérée par son propre panel) —
        // sans ça, un appel API sans jeton valide plante en 500
        // (RouteNotFoundException) au lieu d'un 401 JSON propre.
        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Sans DSN (`SENTRY_LARAVEL_DSN` vide), aucun envoi : sans effet en
        // local et en tests.
        Integration::handles($exceptions);

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

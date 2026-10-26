<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
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
        // Aucun schéma de openapi.yaml n'attend d'enveloppe `data` — les
        // réponses sont soit un objet nu (Account, Profile…), soit un
        // tableau nu, soit un objet explicite `{ items, nextCursor }`
        // (paginé). Le wrapping par défaut de Laravel aurait cassé le
        // parsing JSON côté Flutter sur chaque endpoint qui renvoie une
        // Resource.
        JsonResource::withoutWrapping();

        // Limite globale de l'API, par compte quand un jeton est présenté
        // (garde `sanctum` explicite : ce limiteur peut passer avant
        // `auth:sanctum`), sinon par IP.
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)
            ->by(self::clientKey($request)));

        // Chaque message déclenche un appel Anthropic facturé.
        RateLimiter::for('support-chat', fn (Request $request) => Limit::perMinute(10)
            ->by(self::clientKey($request)));
    }

    private static function clientKey(Request $request): string
    {
        $account = $request->user('sanctum');

        return $account ? "account:{$account->getKey()}" : "ip:{$request->ip()}";
    }
}

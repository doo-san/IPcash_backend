<?php

namespace App\Providers;

use Illuminate\Http\Resources\Json\JsonResource;
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
    }
}

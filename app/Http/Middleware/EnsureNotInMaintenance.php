<?php

namespace App\Http\Middleware;

use App\Support\AppConfig;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Bloque l'API authentifiée pendant une maintenance décidée depuis l'admin.
// `GET /config` reste hors de ce middleware : l'app y lit le message à afficher.
class EnsureNotInMaintenance
{
    public function handle(Request $request, Closure $next): Response
    {
        if (AppConfig::bool('maintenance_enabled')) {
            return response()->json([
                'code' => 'MAINTENANCE',
                'message' => AppConfig::string('maintenance_message') ?: "L'application est en maintenance.",
            ], 503);
        }

        return $next($request);
    }
}

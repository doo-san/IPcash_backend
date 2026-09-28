<?php

namespace App\Http\Middleware;

use App\Support\AppConfig;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Ferme un service désactivé depuis l'admin (Réglages de l'application >
// Services), même si l'app installée l'affiche encore. Usage : `feature:credit`.
class EnsureFeatureEnabled
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        if (! AppConfig::featureEnabled($feature)) {
            return response()->json([
                'code' => 'FEATURE_DISABLED',
                'message' => "Ce service n'est pas disponible pour le moment.",
            ], 403);
        }

        return $next($request);
    }
}

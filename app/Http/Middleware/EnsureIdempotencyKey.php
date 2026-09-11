<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Toute requête qui déplace de l'argent porte un en-tête `Idempotency-Key`
// (UUID v4) obligatoire — CLAUDE.md règle 2, openapi.yaml
// `components.parameters.IdempotencyKey`.
class EnsureIdempotencyKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('Idempotency-Key');

        if (! $key || ! preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $key)) {
            return response()->json([
                'code' => 'BAD_REQUEST',
                'message' => 'En-tête Idempotency-Key manquant ou invalide (UUID v4 attendu).',
            ], 400);
        }

        return $next($request);
    }
}

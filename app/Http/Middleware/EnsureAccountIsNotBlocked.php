<?php

namespace App\Http\Middleware;

use App\Models\Account;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Coupe l'accès aux clients bloqués par l'admin (`Account::isBlocked()`,
// AccountResource) même s'ils tiennent encore un jeton d'accès valide émis
// avant le blocage — `AuthController::login`/`refresh` empêchent déjà
// d'obtenir un *nouveau* jeton, mais un jeton déjà en poche resterait
// sinon utilisable jusqu'à son expiration naturelle.
class EnsureAccountIsNotBlocked
{
    public function handle(Request $request, Closure $next): Response
    {
        $account = $request->user();

        if ($account instanceof Account && $account->isBlocked()) {
            return response()->json([
                'code' => 'ACCOUNT_BLOCKED',
                'message' => $account->block_reason ?? 'Ce compte a été bloqué.',
            ], 403);
        }

        return $next($request);
    }
}

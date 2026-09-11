<?php

namespace App\Http\Middleware;

use App\Models\Account;
use App\Models\OtpRequest;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

// Garde `X-Session-Token` (voir openapi.yaml, securitySchemes.sessionToken)
// pour POST /auth/pin — jeton temporaire émis par /auth/otp/verify, valable
// uniquement entre l'OTP validé et le PIN créé. `otp_requests.session_token`
// stocke le hash (jamais le jeton en clair), comme les autres colonnes
// `*_hash` de ce projet.
class EnsureValidSessionToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-Session-Token');

        if (! $token) {
            return $this->unauthorized('Jeton de session manquant.');
        }

        $otpRequest = OtpRequest::whereNotNull('verified_at')
            ->whereNotNull('session_token_expires_at')
            ->where('session_token_expires_at', '>', now())
            ->get()
            ->first(fn (OtpRequest $candidate) => Hash::check($token, $candidate->session_token));

        if (! $otpRequest) {
            return $this->unauthorized('Jeton de session invalide ou expiré.');
        }

        $account = Account::where('phone_number', $otpRequest->phone_number)->first();

        if (! $account) {
            return $this->unauthorized('Compte introuvable pour ce numéro.');
        }

        $request->attributes->set('otpRequest', $otpRequest);
        $request->attributes->set('account', $account);

        return $next($request);
    }

    private function unauthorized(string $message): Response
    {
        return response()->json([
            'code' => 'UNAUTHORIZED',
            'message' => $message,
        ], 401);
    }
}

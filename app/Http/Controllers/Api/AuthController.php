<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CreatePinRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\PersonalInfoRequest;
use App\Http\Requests\Auth\RefreshTokenRequest;
use App\Http\Requests\Auth\RegisterPhoneRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Resources\AccountResource;
use App\Models\Account;
use App\Models\OtpRequest;
use App\Models\RefreshToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

// Implémente le tag `auth` de openapi.yaml : téléphone -> OTP -> PIN ->
// (prénom/nom une fois authentifié), plus login/refresh/logout. Chaque
// méthode correspond à un seul endpoint du contrat.
class AuthController extends Controller
{
    private const OTP_TTL_SECONDS = 60;

    private const MAX_OTP_ATTEMPTS = 5;

    private const SESSION_TOKEN_TTL_MINUTES = 15;

    private const ACCESS_TOKEN_TTL_MINUTES = 15;

    private const REFRESH_TOKEN_TTL_DAYS = 30;

    private const MAX_PIN_ATTEMPTS = 5;

    private const LOCKOUT_MINUTES = 15;

    public function registerPhone(RegisterPhoneRequest $request): JsonResponse
    {
        $data = $request->validated();

        Account::firstOrCreate(['phone_number' => $data['phoneNumber']]);

        // Aucun fournisseur SMS réel n'est intégré (CLAUDE.md règle 9) —
        // hors production, le code est fixé à 111111 pour rester testable
        // sans SMS, exactement comme `AuthFakeDataSource` côté Flutter.
        $code = app()->environment('production')
            ? (string) random_int(100000, 999999)
            : '111111';

        $otpRequest = OtpRequest::create([
            'phone_number' => $data['phoneNumber'],
            'code_hash' => Hash::make($code),
            'locale' => $data['locale'],
            'expires_at' => now()->addSeconds(self::OTP_TTL_SECONDS),
        ]);

        if (! app()->environment('production')) {
            logger()->info("[dev] OTP {$data['phoneNumber']} = {$code} (aucun SMS envoyé)");
        }

        return response()->json([
            'otpRequestId' => $otpRequest->id,
            'expiresInSeconds' => self::OTP_TTL_SECONDS,
        ]);
    }

    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $data = $request->validated();

        $otpRequest = OtpRequest::find($data['otpRequestId']);

        if (! $otpRequest
            || $otpRequest->verified_at !== null
            || $otpRequest->isExpired()
            || $otpRequest->attempts >= self::MAX_OTP_ATTEMPTS
        ) {
            return $this->error(401, 'INVALID_OTP', 'Code invalide ou expiré.');
        }

        if (! Hash::check($data['code'], $otpRequest->code_hash)) {
            $otpRequest->increment('attempts');

            return $this->error(401, 'INVALID_OTP', 'Code invalide ou expiré.');
        }

        $sessionToken = Str::random(64);

        $otpRequest->update([
            'verified_at' => now(),
            'session_token' => Hash::make($sessionToken),
            'session_token_expires_at' => now()->addMinutes(self::SESSION_TOKEN_TTL_MINUTES),
        ]);

        $account = Account::where('phone_number', $otpRequest->phone_number)->first();
        $isNewUser = $account === null || $account->pin_hash === null;

        return response()->json([
            'sessionToken' => $sessionToken,
            'isNewUser' => $isNewUser,
        ]);
    }

    public function createPin(CreatePinRequest $request): JsonResponse
    {
        /** @var Account $account */
        $account = $request->attributes->get('account');
        /** @var OtpRequest $otpRequest */
        $otpRequest = $request->attributes->get('otpRequest');

        // `pin_hash` est volontairement absent de `$fillable` (protection
        // contre l'assignation de masse ailleurs dans l'app) — assignation
        // directe + `save()` ici, seul endroit légitime pour le poser.
        $account->pin_hash = Hash::make($request->validated('pin'));
        $account->save();

        // Jeton de session à usage unique : invalidé dès que le PIN est
        // posé, il ne doit plus jamais servir à rappeler /auth/pin.
        $otpRequest->update(['session_token_expires_at' => now()]);

        return $this->issueTokens($account, null);
    }

    public function personalInfo(PersonalInfoRequest $request): AccountResource
    {
        /** @var Account $account */
        $account = $request->user();
        $data = $request->validated();

        // `$request->validated()` renvoie les clés camelCase du contrat —
        // ne pas les passer telles quelles à `update()`, les colonnes sont
        // en snake_case (sinon silencieusement ignorées : aucune ne
        // correspond à `$fillable`).
        $account->update([
            'first_name' => $data['firstName'],
            'last_name' => $data['lastName'],
        ]);

        return new AccountResource($account);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        $account = Account::where('phone_number', $data['phoneNumber'])->first();

        if (! $account || $account->pin_hash === null) {
            return $this->error(401, 'UNAUTHORIZED', 'Numéro ou code secret invalide.');
        }

        if ($account->isBlocked()) {
            return $this->error(403, 'ACCOUNT_BLOCKED', $account->block_reason ?? 'Ce compte a été bloqué.');
        }

        if ($account->isLocked()) {
            return $this->error(423, 'ACCOUNT_LOCKED', 'Compte verrouillé — trop de tentatives échouées.');
        }

        if (! Hash::check($data['pin'], $account->pin_hash)) {
            $account->increment('failed_pin_attempts');

            if ($account->failed_pin_attempts >= self::MAX_PIN_ATTEMPTS) {
                // `locked_until` non fillable (même raison que `pin_hash`) —
                // assignation directe.
                $account->locked_until = now()->addMinutes(self::LOCKOUT_MINUTES);
                $account->save();

                return $this->error(423, 'ACCOUNT_LOCKED', 'Compte verrouillé — trop de tentatives échouées.');
            }

            return $this->error(401, 'UNAUTHORIZED', 'Numéro ou code secret invalide.');
        }

        $account->failed_pin_attempts = 0;
        $account->locked_until = null;
        $account->save();

        return $this->issueTokens($account, $data['deviceId']);
    }

    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        $rawToken = $request->validated('refreshToken');

        /** @var RefreshToken|null $refreshToken */
        $refreshToken = RefreshToken::whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->get()
            ->first(fn (RefreshToken $candidate) => Hash::check($rawToken, $candidate->token_hash));

        if (! $refreshToken) {
            return $this->error(401, 'UNAUTHORIZED', 'Jeton de rafraîchissement invalide ou expiré.');
        }

        if ($refreshToken->account->isBlocked()) {
            return $this->error(403, 'ACCOUNT_BLOCKED', $refreshToken->account->block_reason ?? 'Ce compte a été bloqué.');
        }

        // Rotation : l'ancien refresh token ne resert jamais une fois
        // échangé contre une nouvelle paire.
        $refreshToken->update(['revoked_at' => now()]);

        return $this->issueTokens($refreshToken->account, $refreshToken->device_id);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var Account $account */
        $account = $request->user();

        $account->currentAccessToken()->delete();
        $account->refreshTokens()->whereNull('revoked_at')->update(['revoked_at' => now()]);

        return response()->json(null, 204);
    }

    private function issueTokens(Account $account, ?string $deviceId): JsonResponse
    {
        $accessToken = $account->createToken('mobile')->plainTextToken;

        $rawRefreshToken = Str::random(64);
        $account->refreshTokens()->create([
            'device_id' => $deviceId,
            'token_hash' => Hash::make($rawRefreshToken),
            'expires_at' => now()->addDays(self::REFRESH_TOKEN_TTL_DAYS),
        ]);

        return response()->json([
            'accessToken' => $accessToken,
            'refreshToken' => $rawRefreshToken,
            'expiresInSeconds' => self::ACCESS_TOKEN_TTL_MINUTES * 60,
        ]);
    }

    private function error(int $status, string $code, string $message): JsonResponse
    {
        return response()->json(['code' => $code, 'message' => $message], $status);
    }
}

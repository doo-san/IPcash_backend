<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Couvre /auth/* de bout en bout — voir api/openapi.yaml. En environnement
// non-production, `AuthController::registerPhone` fixe toujours le code à
// 111111 (aucun fournisseur SMS réel, CLAUDE.md règle 9), ce qui rend ce
// test déterministe.
class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    private const PHONE = '+221771234567';

    public function test_full_registration_to_pin_creation_flow(): void
    {
        $register = $this->postJson('/api/auth/register/phone', [
            'phoneNumber' => self::PHONE,
            'locale' => 'fr',
        ])->assertOk()->json();

        $this->assertArrayHasKey('otpRequestId', $register);

        $wrongCode = $this->postJson('/api/auth/otp/verify', [
            'otpRequestId' => $register['otpRequestId'],
            'code' => '000000',
        ]);
        $wrongCode->assertStatus(401);

        $verify = $this->postJson('/api/auth/otp/verify', [
            'otpRequestId' => $register['otpRequestId'],
            'code' => '111111',
        ])->assertOk()->json();

        $this->assertTrue($verify['isNewUser']);

        $pin = $this->postJson('/api/auth/pin', ['pin' => '123456'], [
            'X-Session-Token' => $verify['sessionToken'],
        ])->assertOk()->json();

        $this->assertArrayHasKey('accessToken', $pin);
        $this->assertArrayHasKey('refreshToken', $pin);

        // Le jeton de session est à usage unique.
        $this->postJson('/api/auth/pin', ['pin' => '999999'], [
            'X-Session-Token' => $verify['sessionToken'],
        ])->assertStatus(401);

        $personalInfo = $this->postJson('/api/auth/register/personal-info', [
            'firstName' => 'Abdou',
            'lastName' => 'Ba',
        ], [
            'Authorization' => "Bearer {$pin['accessToken']}",
        ])->assertOk()->json();

        $this->assertSame('Abdou', $personalInfo['firstName']);
        $this->assertSame('Ba', $personalInfo['lastName']);
    }

    public function test_login_locks_account_after_too_many_failed_attempts(): void
    {
        $account = Account::create(['phone_number' => self::PHONE]);
        $account->pin_hash = bcrypt('123456');
        $account->save();

        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/auth/login', [
                'phoneNumber' => self::PHONE,
                'pin' => '000000',
                'deviceId' => 'device-1',
            ]);
        }
        $response->assertStatus(423);

        // Même le bon PIN est refusé une fois verrouillé.
        $this->postJson('/api/auth/login', [
            'phoneNumber' => self::PHONE,
            'pin' => '123456',
            'deviceId' => 'device-1',
        ])->assertStatus(423);
    }

    public function test_refresh_token_rotates_and_cannot_be_reused(): void
    {
        $account = Account::create(['phone_number' => self::PHONE]);
        $account->pin_hash = bcrypt('123456');
        $account->save();

        $login = $this->postJson('/api/auth/login', [
            'phoneNumber' => self::PHONE,
            'pin' => '123456',
            'deviceId' => 'device-1',
        ])->assertOk()->json();

        $refreshed = $this->postJson('/api/auth/refresh', [
            'refreshToken' => $login['refreshToken'],
        ])->assertOk()->json();

        $this->assertNotSame($login['refreshToken'], $refreshed['refreshToken']);

        $this->postJson('/api/auth/refresh', [
            'refreshToken' => $login['refreshToken'],
        ])->assertStatus(401);
    }

    public function test_logout_revokes_access_and_refresh_tokens(): void
    {
        $account = Account::create(['phone_number' => self::PHONE]);
        $account->pin_hash = bcrypt('123456');
        $account->save();

        $login = $this->postJson('/api/auth/login', [
            'phoneNumber' => self::PHONE,
            'pin' => '123456',
            'deviceId' => 'device-1',
        ])->assertOk()->json();

        $this->postJson('/api/auth/logout', [], [
            'Authorization' => "Bearer {$login['accessToken']}",
        ])->assertStatus(204);

        // Entre deux appels `postJson()` simulant deux requêtes distinctes,
        // le guard Sanctum garde en cache l'utilisateur résolu par le
        // premier appel authentifié (artefact du harnais de test — en
        // production chaque requête HTTP est un processus séparé, ce
        // problème n'existe pas). Sans ce `forgetGuards()`, l'appel suivant
        // réutiliserait le compte mis en cache au lieu de re-résoudre le
        // token (maintenant supprimé) et renverrait à tort 200.
        auth()->forgetGuards();

        $this->postJson('/api/auth/register/personal-info', [
            'firstName' => 'X',
            'lastName' => 'Y',
        ], [
            'Authorization' => "Bearer {$login['accessToken']}",
        ])->assertStatus(401);

        $this->postJson('/api/auth/refresh', [
            'refreshToken' => $login['refreshToken'],
        ])->assertStatus(401);
    }

    public function test_unauthenticated_request_returns_clean_401_not_500(): void
    {
        $this->postJson('/api/auth/register/personal-info', [
            'firstName' => 'X',
            'lastName' => 'Y',
        ])->assertStatus(401);
    }

    public function test_a_blocked_account_cannot_log_in(): void
    {
        $account = Account::create(['phone_number' => self::PHONE]);
        $account->pin_hash = bcrypt('123456');
        $account->blocked_at = now();
        $account->block_reason = 'Fraude signalée';
        $account->save();

        $this->postJson('/api/auth/login', [
            'phoneNumber' => self::PHONE,
            'pin' => '123456',
            'deviceId' => 'device-1',
        ])->assertStatus(403)->assertJson(['code' => 'ACCOUNT_BLOCKED', 'message' => 'Fraude signalée']);
    }

    public function test_a_blocked_account_cannot_refresh_its_token(): void
    {
        $account = Account::create(['phone_number' => self::PHONE]);
        $account->pin_hash = bcrypt('123456');
        $account->save();

        $login = $this->postJson('/api/auth/login', [
            'phoneNumber' => self::PHONE,
            'pin' => '123456',
            'deviceId' => 'device-1',
        ])->assertOk()->json();

        $account->blocked_until = now()->addDay();
        $account->save();
        auth()->forgetGuards();

        $this->postJson('/api/auth/refresh', [
            'refreshToken' => $login['refreshToken'],
        ])->assertStatus(403)->assertJson(['code' => 'ACCOUNT_BLOCKED']);
    }

    public function test_blocking_an_account_mid_session_revokes_access_immediately(): void
    {
        $account = Account::create(['phone_number' => self::PHONE]);
        $account->pin_hash = bcrypt('123456');
        $account->save();

        $login = $this->postJson('/api/auth/login', [
            'phoneNumber' => self::PHONE,
            'pin' => '123456',
            'deviceId' => 'device-1',
        ])->assertOk()->json();

        // Le jeton d'accès reste techniquement valide (non révoqué) —
        // c'est `EnsureAccountIsNotBlocked` qui doit couper l'accès malgré
        // tout, pas seulement `login`/`refresh`.
        $account->blocked_at = now();
        $account->save();
        auth()->forgetGuards();

        $this->getJson('/api/accounts/me', [
            'Authorization' => "Bearer {$login['accessToken']}",
        ])->assertStatus(403)->assertJson(['code' => 'ACCOUNT_BLOCKED']);
    }

    public function test_an_expired_temporary_block_no_longer_prevents_login(): void
    {
        $account = Account::create(['phone_number' => self::PHONE]);
        $account->pin_hash = bcrypt('123456');
        $account->blocked_until = now()->subMinute();
        $account->save();

        $this->postJson('/api/auth/login', [
            'phoneNumber' => self::PHONE,
            'pin' => '123456',
            'deviceId' => 'device-1',
        ])->assertOk();
    }
}

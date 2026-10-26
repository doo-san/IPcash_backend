<?php

namespace Tests\Feature\Api;

use App\Enums\KycVerificationStatus;
use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AccountClosureTest extends TestCase
{
    use RefreshDatabase;

    private const PHONE = '+221771230000';

    private function account(int $balance = 0): Account
    {
        $account = Account::create(['phone_number' => self::PHONE, 'first_name' => 'Awa', 'last_name' => 'Diop']);
        $account->pin_hash = bcrypt('123456');
        $account->balance_xof = $balance;
        $account->kyc_status = KycVerificationStatus::Verified;
        $account->save();

        return $account;
    }

    private function headers(Account $account): array
    {
        auth()->forgetGuards();

        return ['Authorization' => 'Bearer '.$account->createToken('test')->plainTextToken];
    }

    public function test_eligibility_lists_what_blocks_the_closure(): void
    {
        $account = $this->account(500);
        $account->pockets()->create(['name' => 'Vacances', 'balance_xof' => 200]);
        $account->transactions()->create(['type' => 'cashIn', 'status' => 'pending', 'amount_xof' => 100, 'reference' => 'P-1']);

        $this->getJson('/api/profile/closure', $this->headers($account))
            ->assertOk()
            ->assertJson([
                'eligible' => false,
                'blockers' => ['MAIN_BALANCE', 'POCKET_BALANCE', 'PENDING_TRANSACTION'],
                'retentionYears' => 10,
            ]);
    }

    public function test_closure_is_refused_with_a_wrong_pin_or_while_money_remains(): void
    {
        $empty = $this->account(0);
        $this->postJson('/api/profile/close', ['pin' => '000000'], $this->headers($empty))->assertStatus(401);
        $this->assertFalse($empty->fresh()->isClosed());

        $empty->balance_xof = 1000;
        $empty->save();
        $this->postJson('/api/profile/close', ['pin' => '123456'], $this->headers($empty))
            ->assertStatus(409)
            ->assertJson(['code' => 'ACCOUNT_CLOSURE_BLOCKED', 'details' => ['blockers' => ['MAIN_BALANCE']]]);
        $this->assertFalse($empty->fresh()->isClosed());
    }

    public function test_closure_cuts_access_erases_optional_data_and_keeps_legal_records(): void
    {
        $account = $this->account(0);
        $account->chatMessages()->create(['role' => 'user', 'text' => 'Bonjour']);
        $account->beneficiaries()->create(['name' => 'Moussa', 'phone_number' => '+221770000099']);
        $account->pockets()->create(['name' => 'Vide', 'balance_xof' => 0]);
        $account->transactions()->create(['type' => 'cashIn', 'status' => 'completed', 'amount_xof' => 100, 'reference' => 'C-1']);
        $account->kycDocuments()->create([
            'document_type' => 'nationalId', 'front_path' => 'kyc/x/front.jpg', 'selfie_path' => 'kyc/x/selfie.jpg',
            'status' => 'verified', 'submitted_at' => now(),
        ]);
        $headers = $this->headers($account);

        $this->postJson('/api/profile/close', ['pin' => '123456'], $headers)->assertNoContent();

        $account->refresh();
        $this->assertTrue($account->isClosed());
        $this->assertSame(self::PHONE, $account->closed_phone_number);
        $this->assertSame('closed:'.$account->id, $account->phone_number);
        $this->assertNull($account->pin_hash);
        $this->assertSame(0, $account->tokens()->count());
        $this->assertSame(0, $account->chatMessages()->count());
        $this->assertSame(0, $account->beneficiaries()->count());
        $this->assertSame(0, $account->pockets()->count());
        $this->assertSame(1, $account->transactions()->count());
        $this->assertSame(1, $account->kycDocuments()->count());

        auth()->forgetGuards();
        $this->getJson('/api/accounts/me', $headers)->assertStatus(401);
    }

    public function test_the_phone_number_can_register_a_new_account_after_closure(): void
    {
        $old = $this->account(0);
        $this->postJson('/api/profile/close', ['pin' => '123456'], $this->headers($old))->assertNoContent();

        auth()->forgetGuards();
        $register = $this->postJson('/api/auth/register/phone', ['phoneNumber' => self::PHONE, 'locale' => 'fr'])
            ->assertOk()->json();
        $verify = $this->postJson('/api/auth/otp/verify', ['otpRequestId' => $register['otpRequestId'], 'code' => '111111'])
            ->assertOk()->json();

        $this->assertTrue($verify['isNewUser']);
        $this->assertSame(2, Account::count());
        $this->assertTrue($old->fresh()->isClosed());
    }

    public function test_closed_accounts_are_purged_with_their_files_only_after_the_retention_period(): void
    {
        Storage::fake('kyc');
        Storage::disk('kyc')->put('kyc/old/front.jpg', 'id');
        $old = $this->account(0);
        $old->kycDocuments()->create([
            'document_type' => 'nationalId', 'front_path' => 'kyc/old/front.jpg', 'selfie_path' => 'kyc/old/front.jpg',
            'status' => 'verified', 'submitted_at' => now(),
        ]);
        $old->forceFill(['closed_at' => now()->subYears(10)->subDay(), 'closed_phone_number' => self::PHONE, 'phone_number' => 'closed:a'])->save();

        $recent = Account::create(['phone_number' => 'closed:b']);
        $recent->forceFill(['closed_at' => now()->subYears(9)])->save();

        $this->artisan('accounts:purge-closed')->assertExitCode(0);

        $this->assertNull(Account::find($old->id));
        $this->assertNotNull(Account::find($recent->id));
        Storage::disk('kyc')->assertMissing('kyc/old/front.jpg');
    }
}

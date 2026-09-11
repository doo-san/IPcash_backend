<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\FeeRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TransferFlowTest extends TestCase
{
    use RefreshDatabase;

    private function verifiedAccount(string $phone, int $balance = 100000): Account
    {
        $account = Account::create(['phone_number' => $phone]);
        $account->pin_hash = bcrypt('123456');
        $account->kyc_status = 'verified';
        $account->balance_xof = $balance;
        $account->save();

        return $account;
    }

    private function authHeader(Account $account): array
    {
        return ['Authorization' => 'Bearer '.$account->createToken('test')->plainTextToken];
    }

    public function test_quote_includes_fee_from_active_fee_rule(): void
    {
        FeeRule::create(['scope' => 'p2pTransfer', 'type' => 'percent', 'value' => 50]);
        $sender = $this->verifiedAccount('+221771111111');
        $this->verifiedAccount('+221772222222');

        $quote = $this->postJson('/api/transfers/quote', [
            'recipientPhoneNumber' => '+221772222222',
            'amountXof' => 10000,
        ], $this->authHeader($sender))->assertOk()->json();

        $this->assertSame(10000, $quote['amountXof']);
        $this->assertSame(50, $quote['feeXof']);
        $this->assertSame(10050, $quote['totalXof']);
    }

    public function test_p2p_transfer_moves_balance_and_creates_both_ledger_entries(): void
    {
        FeeRule::create(['scope' => 'p2pTransfer', 'type' => 'percent', 'value' => 50]);
        $sender = $this->verifiedAccount('+221771111111', 100000);
        $recipient = $this->verifiedAccount('+221772222222', 0);

        $response = $this->postJson('/api/transfers/p2p', [
            'recipientPhoneNumber' => '+221772222222',
            'amountXof' => 10000,
            'pin' => '123456',
        ], array_merge($this->authHeader($sender), ['Idempotency-Key' => Str::uuid()->toString()]));

        $response->assertStatus(202)->assertJson(['type' => 'transferOut', 'amountXof' => -10050]);

        $this->assertSame(89950, $sender->fresh()->balance_xof);
        $this->assertSame(10000, $recipient->fresh()->balance_xof);
        $this->assertSame(1, $sender->transactions()->count());
        $this->assertSame(1, $recipient->transactions()->count());
    }

    public function test_p2p_transfer_requires_idempotency_key(): void
    {
        $sender = $this->verifiedAccount('+221771111111');
        $this->verifiedAccount('+221772222222');

        $this->postJson('/api/transfers/p2p', [
            'recipientPhoneNumber' => '+221772222222',
            'amountXof' => 10000,
            'pin' => '123456',
        ], $this->authHeader($sender))->assertStatus(400);
    }

    public function test_p2p_transfer_replays_idempotently_without_double_debit(): void
    {
        $sender = $this->verifiedAccount('+221771111111', 100000);
        $this->verifiedAccount('+221772222222');
        $key = Str::uuid()->toString();
        $headers = array_merge($this->authHeader($sender), ['Idempotency-Key' => $key]);

        $payload = ['recipientPhoneNumber' => '+221772222222', 'amountXof' => 10000, 'pin' => '123456'];

        $first = $this->postJson('/api/transfers/p2p', $payload, $headers)->assertStatus(202)->json();
        $second = $this->postJson('/api/transfers/p2p', $payload, $headers)->assertStatus(202)->json();

        $this->assertSame($first['id'], $second['id']);
        $this->assertSame(90000, $sender->fresh()->balance_xof); // débité une seule fois
    }

    public function test_p2p_transfer_conflicts_when_same_key_used_with_different_amount(): void
    {
        $sender = $this->verifiedAccount('+221771111111', 100000);
        $this->verifiedAccount('+221772222222');
        $key = Str::uuid()->toString();
        $headers = array_merge($this->authHeader($sender), ['Idempotency-Key' => $key]);

        $this->postJson('/api/transfers/p2p', [
            'recipientPhoneNumber' => '+221772222222', 'amountXof' => 10000, 'pin' => '123456',
        ], $headers)->assertStatus(202);

        $this->postJson('/api/transfers/p2p', [
            'recipientPhoneNumber' => '+221772222222', 'amountXof' => 99999, 'pin' => '123456',
        ], $headers)->assertStatus(409);
    }

    public function test_p2p_transfer_rejects_insufficient_funds(): void
    {
        $sender = $this->verifiedAccount('+221771111111', 100);
        $this->verifiedAccount('+221772222222');

        $this->postJson('/api/transfers/p2p', [
            'recipientPhoneNumber' => '+221772222222', 'amountXof' => 10000, 'pin' => '123456',
        ], array_merge($this->authHeader($sender), ['Idempotency-Key' => Str::uuid()->toString()]))
            ->assertStatus(402);

        $this->assertSame(100, $sender->fresh()->balance_xof);
    }

    public function test_p2p_transfer_rejects_wrong_pin(): void
    {
        $sender = $this->verifiedAccount('+221771111111');
        $this->verifiedAccount('+221772222222');

        $this->postJson('/api/transfers/p2p', [
            'recipientPhoneNumber' => '+221772222222', 'amountXof' => 10000, 'pin' => '000000',
        ], array_merge($this->authHeader($sender), ['Idempotency-Key' => Str::uuid()->toString()]))
            ->assertStatus(401);
    }

    public function test_p2p_transfer_requires_verified_kyc(): void
    {
        $sender = Account::create(['phone_number' => '+221771111111']);
        $sender->pin_hash = bcrypt('123456');
        $sender->balance_xof = 100000;
        $sender->save(); // kyc_status reste "notStarted"
        $this->verifiedAccount('+221772222222');

        $this->postJson('/api/transfers/p2p', [
            'recipientPhoneNumber' => '+221772222222', 'amountXof' => 10000, 'pin' => '123456',
        ], array_merge([
            'Authorization' => 'Bearer '.$sender->createToken('test')->plainTextToken,
        ], ['Idempotency-Key' => Str::uuid()->toString()]))
            ->assertStatus(403);
    }

    public function test_transfer_to_unknown_recipient_is_rejected(): void
    {
        $sender = $this->verifiedAccount('+221771111111');

        $this->postJson('/api/transfers/quote', [
            'recipientPhoneNumber' => '+221700000000',
            'amountXof' => 10000,
        ], $this->authHeader($sender))->assertStatus(400);
    }

    public function test_transfer_to_self_is_rejected(): void
    {
        $sender = $this->verifiedAccount('+221771111111');

        $this->postJson('/api/transfers/quote', [
            'recipientPhoneNumber' => '+221771111111',
            'amountXof' => 10000,
        ], $this->authHeader($sender))->assertStatus(400);
    }
}

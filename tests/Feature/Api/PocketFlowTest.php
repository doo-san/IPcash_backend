<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PocketFlowTest extends TestCase
{
    use RefreshDatabase;

    private function account(int $balance = 0): Account
    {
        $account = Account::create(['phone_number' => '+2217713'.random_int(10000, 99999)]);
        $account->balance_xof = $balance;
        $account->save();

        return $account;
    }

    private function authHeader(Account $account): array
    {
        return ['Authorization' => 'Bearer '.$account->createToken('test')->plainTextToken];
    }

    private function idempotentHeader(Account $account): array
    {
        return array_merge($this->authHeader($account), ['Idempotency-Key' => Str::uuid()->toString()]);
    }

    public function test_create_list_rename_and_delete_a_pocket(): void
    {
        $account = $this->account();

        $created = $this->postJson('/api/pockets', ['name' => 'Vacances'], $this->authHeader($account))
            ->assertStatus(201)->json();
        $this->assertSame('Vacances', $created['name']);
        $this->assertSame(0, $created['balanceXof']);

        $list = $this->getJson('/api/pockets', $this->authHeader($account))->assertOk()->json();
        $this->assertCount(1, $list);

        $renamed = $this->patchJson("/api/pockets/{$created['id']}", ['name' => 'Vacances 2027'], $this->authHeader($account))
            ->assertOk()->json();
        $this->assertSame('Vacances 2027', $renamed['name']);

        $this->deleteJson("/api/pockets/{$created['id']}", [], $this->authHeader($account))->assertStatus(204);
        $this->assertCount(0, $account->pockets()->get());
    }

    public function test_deleting_a_non_empty_pocket_is_blocked(): void
    {
        $account = $this->account(10000);
        $pocket = $account->pockets()->create(['name' => 'Coffre']);
        $this->postJson("/api/pockets/{$pocket->id}/transfer-in", ['amountXof' => 5000], $this->idempotentHeader($account))
            ->assertStatus(202);

        $this->deleteJson("/api/pockets/{$pocket->id}", [], $this->authHeader($account))->assertStatus(400);
        $this->assertNotNull($account->pockets()->find($pocket->id));
    }

    public function test_transfer_in_moves_money_from_main_balance_to_pocket(): void
    {
        $account = $this->account(20000);
        $pocket = $account->pockets()->create(['name' => 'Coffre']);

        $tx = $this->postJson("/api/pockets/{$pocket->id}/transfer-in", ['amountXof' => 7000], $this->idempotentHeader($account))
            ->assertStatus(202)->json();

        $this->assertSame('pocketTransferIn', $tx['type']);
        $this->assertSame(7000, $tx['amountXof']);
        $this->assertSame(13000, $tx['mainBalanceAfterXof']);
        $this->assertSame(7000, $tx['pocketBalanceAfterXof']);
        $this->assertSame('Coffre', $tx['pocketName']);
        $this->assertSame(13000, $account->fresh()->balance_xof);
        $this->assertSame(7000, $pocket->fresh()->balance_xof);
    }

    public function test_transfer_in_exceeding_main_balance_is_rejected(): void
    {
        $account = $this->account(1000);
        $pocket = $account->pockets()->create(['name' => 'Coffre']);

        $this->postJson("/api/pockets/{$pocket->id}/transfer-in", ['amountXof' => 5000], $this->idempotentHeader($account))
            ->assertStatus(402);
    }

    public function test_transfer_out_moves_money_from_pocket_to_main_balance(): void
    {
        $account = $this->account(0);
        $pocket = $account->pockets()->create(['name' => 'Coffre', 'balance_xof' => 10000]);

        $tx = $this->postJson("/api/pockets/{$pocket->id}/transfer-out", ['amountXof' => 4000], $this->idempotentHeader($account))
            ->assertStatus(202)->json();

        $this->assertSame('pocketTransferOut', $tx['type']);
        $this->assertSame(-4000, $tx['amountXof']);
        $this->assertSame(4000, $account->fresh()->balance_xof);
        $this->assertSame(6000, $pocket->fresh()->balance_xof);
    }

    public function test_transfer_out_of_a_locked_pocket_is_blocked(): void
    {
        $account = $this->account(0);
        $pocket = $account->pockets()->create([
            'name' => 'Coffre', 'balance_xof' => 10000, 'locked_until' => now()->addDay(),
        ]);

        $this->postJson("/api/pockets/{$pocket->id}/transfer-out", ['amountXof' => 1000], $this->idempotentHeader($account))
            ->assertStatus(400)->assertJson(['code' => 'POCKET_LOCKED']);
    }

    public function test_lock_and_unlock_a_pocket(): void
    {
        $account = $this->account();
        $pocket = $account->pockets()->create(['name' => 'Coffre']);

        $locked = $this->postJson("/api/pockets/{$pocket->id}/lock", ['until' => now()->addWeek()->toIso8601String()], $this->authHeader($account))
            ->assertOk()->json();
        $this->assertNotNull($locked['lockedUntil']);

        $unlocked = $this->postJson("/api/pockets/{$pocket->id}/unlock", [], $this->authHeader($account))
            ->assertOk()->json();
        $this->assertNull($unlocked['lockedUntil']);
    }

    public function test_pocket_transactions_are_scoped_to_that_pocket(): void
    {
        $account = $this->account(50000);
        $pocketA = $account->pockets()->create(['name' => 'A']);
        $pocketB = $account->pockets()->create(['name' => 'B']);

        $this->postJson("/api/pockets/{$pocketA->id}/transfer-in", ['amountXof' => 1000], $this->idempotentHeader($account))->assertStatus(202);
        auth()->forgetGuards();
        $this->postJson("/api/pockets/{$pocketB->id}/transfer-in", ['amountXof' => 2000], $this->idempotentHeader($account))->assertStatus(202);

        $historyA = $this->getJson("/api/pockets/{$pocketA->id}/transactions", $this->authHeader($account))->assertOk()->json();
        $this->assertCount(1, $historyA);
        $this->assertSame(1000, $historyA[0]['amountXof']);
    }

    public function test_pocket_actions_are_scoped_to_own_account(): void
    {
        $account = $this->account();
        $other = $this->account();
        $pocket = $other->pockets()->create(['name' => 'Coffre']);

        $this->patchJson("/api/pockets/{$pocket->id}", ['name' => 'Hack'], $this->authHeader($account))->assertStatus(404);
    }
}

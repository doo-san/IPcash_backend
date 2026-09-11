<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BeneficiaryFlowTest extends TestCase
{
    use RefreshDatabase;

    private function authHeader(Account $account): array
    {
        return ['Authorization' => 'Bearer '.$account->createToken('test')->plainTextToken];
    }

    public function test_add_and_list_beneficiaries_scoped_to_own_account(): void
    {
        $account = Account::create(['phone_number' => '+221770000030']);
        $other = Account::create(['phone_number' => '+221770000031']);
        $other->beneficiaries()->create(['name' => 'Pas moi', 'phone_number' => '+221779999999']);

        $created = $this->postJson('/api/beneficiaries', [
            'name' => 'Awa Diallo',
            'phoneNumber' => '+221771112233',
        ], $this->authHeader($account))->assertStatus(201)->json();

        $this->assertSame('Awa Diallo', $created['name']);
        $this->assertSame('+221771112233', $created['phoneNumber']);
        $this->assertArrayHasKey('createdAt', $created);

        $list = $this->getJson('/api/beneficiaries', $this->authHeader($account))->assertOk()->json();
        $this->assertCount(1, $list);
        $this->assertSame('Awa Diallo', $list[0]['name']);
    }
}

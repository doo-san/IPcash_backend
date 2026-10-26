<?php

namespace Tests\Feature;

use App\Filament\Resources\AccountResource\Widgets\AccountActionsWidget;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminAccountClosureTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return tap(User::factory()->create(), fn ($u) => $u->assignRole(Role::findOrCreate($role)));
    }

    public function test_super_admin_closes_an_account_and_keeps_its_legal_records(): void
    {
        $account = Account::create(['phone_number' => '+221770007001']);
        $account->transactions()->create(['type' => 'cashIn', 'status' => 'completed', 'amount_xof' => 100, 'reference' => 'A-1']);

        Livewire::actingAs($this->user('super_admin'))
            ->test(AccountActionsWidget::class, ['record' => $account])
            ->callAction('close')
            ->assertNotified('Compte clôturé');

        $account->refresh();
        $this->assertTrue($account->isClosed());
        $this->assertSame('+221770007001', $account->displayPhoneNumber());
        $this->assertSame(1, $account->transactions()->count());
    }

    public function test_closing_with_money_left_is_refused(): void
    {
        $account = Account::create(['phone_number' => '+221770007002']);
        $account->balance_xof = 500;
        $account->save();

        Livewire::actingAs($this->user('super_admin'))
            ->test(AccountActionsWidget::class, ['record' => $account])
            ->callAction('close')
            ->assertNotified('Clôture impossible');

        $this->assertFalse($account->fresh()->isClosed());
    }

    public function test_hard_delete_also_removes_identity_files(): void
    {
        Storage::fake('kyc');
        Storage::disk('kyc')->put('kyc/t/front.jpg', 'id');
        $account = Account::create(['phone_number' => '+221770007003']);
        $account->kycDocuments()->create([
            'document_type' => 'nationalId', 'front_path' => 'kyc/t/front.jpg', 'selfie_path' => 'kyc/t/front.jpg',
            'status' => 'verified', 'submitted_at' => now(),
        ]);

        Livewire::actingAs($this->user('super_admin'))
            ->test(AccountActionsWidget::class, ['record' => $account])
            ->callAction('delete');

        $this->assertNull(Account::find($account->id));
        Storage::disk('kyc')->assertMissing('kyc/t/front.jpg');
    }

    public function test_support_can_neither_close_nor_delete(): void
    {
        $account = Account::create(['phone_number' => '+221770007004']);

        Livewire::actingAs($this->user('support'))
            ->test(AccountActionsWidget::class, ['record' => $account])
            ->assertActionHidden('close')
            ->assertActionHidden('delete');
    }
}

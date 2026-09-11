<?php

namespace Tests\Feature;

use App\Filament\Resources\AccountResource\Pages\ViewAccount;
use App\Filament\Resources\AccountResource\RelationManagers\TransactionsRelationManager;
use App\Filament\Resources\AccountResource\Widgets\AccountActionsWidget;
use App\Filament\Resources\CardResource\Pages\EditCard;
use App\Models\Account;
use App\Models\Card;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

// Couvre les demandes produit : suppression d'un client (avec garde-fou
// tant qu'il reste des fonds quelque part), suppression d'une carte (le
// solde restant est toujours reversé, jamais perdu), et le suivi des
// transactions d'un client directement depuis sa fiche.
class AdminDeletionAndTrackingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
    }

    public function test_an_account_with_zero_balance_can_be_deleted(): void
    {
        $account = Account::create(['phone_number' => '+221770000020']);

        Livewire::actingAs($this->admin)
            ->test(AccountActionsWidget::class, ['record' => $account])
            ->callAction('delete');

        $this->assertNull(Account::find($account->id));

        $activity = Activity::query()->where('subject_type', Account::class)->where('event', 'deleted')->first();
        $this->assertNotNull($activity, 'La suppression doit être tracée.');
        $this->assertSame($this->admin->id, $activity->causer_id);
    }

    public function test_an_account_with_a_nonzero_main_balance_cannot_be_deleted(): void
    {
        $account = Account::create(['phone_number' => '+221770000021']);
        $account->balance_xof = 5000;
        $account->save();

        Livewire::actingAs($this->admin)
            ->test(AccountActionsWidget::class, ['record' => $account])
            ->callAction('delete');

        $this->assertNotNull(Account::find($account->id), 'Un compte avec des fonds ne doit pas pouvoir être supprimé.');
    }

    public function test_an_account_with_money_left_on_a_card_cannot_be_deleted(): void
    {
        $account = Account::create(['phone_number' => '+221770000022']);
        $account->cards()->create(['last4' => '4242', 'expiry_month' => 9, 'expiry_year' => 2028, 'balance_xof' => 1000]);

        Livewire::actingAs($this->admin)
            ->test(AccountActionsWidget::class, ['record' => $account])
            ->callAction('delete');

        $this->assertNotNull(Account::find($account->id));
    }

    public function test_cancelling_a_card_sweeps_its_balance_back_and_deletes_it(): void
    {
        $account = Account::create(['phone_number' => '+221770000023']);
        $card = $account->cards()->create(['last4' => '4242', 'expiry_month' => 9, 'expiry_year' => 2028, 'balance_xof' => 12000]);

        Livewire::actingAs($this->admin)
            ->test(EditCard::class, ['record' => $card->getRouteKey()])
            ->callAction('deleteCard');

        $this->assertSame(12000, $account->fresh()->balance_xof);
        $this->assertNull(Card::find($card->id));

        $tx = $account->transactions()->latest()->first();
        $this->assertSame('cardWithdrawal', $tx->type->value);
        $this->assertSame(12000, $tx->amount_xof);
    }

    public function test_the_account_view_page_lists_its_transactions(): void
    {
        $account = Account::create(['phone_number' => '+221770000024']);
        $account->transactions()->create([
            'type' => 'transferIn', 'status' => 'completed', 'amount_xof' => 3000, 'reference' => 'TX-TRACK-1',
        ]);

        Livewire::actingAs($this->admin)
            ->test(ViewAccount::class, ['record' => $account->getRouteKey()])
            ->assertOk();

        Livewire::actingAs($this->admin)
            ->test(TransactionsRelationManager::class, ['ownerRecord' => $account, 'pageClass' => ViewAccount::class])
            ->assertCanSeeTableRecords($account->transactions);
    }
}

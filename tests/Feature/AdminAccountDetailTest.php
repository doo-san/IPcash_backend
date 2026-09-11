<?php

namespace Tests\Feature;

use App\Filament\Resources\AccountResource\Pages\ViewAccount;
use App\Filament\Resources\AccountResource\RelationManagers\BeneficiariesRelationManager;
use App\Filament\Resources\AccountResource\RelationManagers\BillAccountsRelationManager;
use App\Filament\Resources\AccountResource\RelationManagers\CardsRelationManager;
use App\Filament\Resources\AccountResource\RelationManagers\ForeignBalancesRelationManager;
use App\Filament\Resources\AccountResource\RelationManagers\PocketsRelationManager;
use App\Filament\Resources\AccountResource\Widgets\AccountActionsWidget;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

// Exigence produit : depuis « Comptes clients », le staff doit pouvoir
// réinitialiser le code secret d'un client, et voir directement sur sa
// fiche son historique de transactions (déjà couvert par
// AdminDeletionAndTrackingTest), ses cartes et ses sous-comptes
// (poches d'épargne, sous-comptes devise).
class AdminAccountDetailTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
    }

    public function test_resetting_the_pin_clears_it_and_any_lockout(): void
    {
        $account = Account::create(['phone_number' => '+221770000050']);
        $account->pin_hash = bcrypt('123456');
        $account->failed_pin_attempts = 3;
        $account->locked_until = now()->addMinutes(10);
        $account->save();

        Livewire::actingAs($this->admin)
            ->test(AccountActionsWidget::class, ['record' => $account])
            ->callAction('resetPin');

        $account->refresh();
        $this->assertNull($account->pin_hash);
        $this->assertSame(0, $account->failed_pin_attempts);
        $this->assertNull($account->locked_until);
    }

    public function test_reset_pin_action_is_hidden_once_no_pin_is_set(): void
    {
        $account = Account::create(['phone_number' => '+221770000051']);

        Livewire::actingAs($this->admin)
            ->test(AccountActionsWidget::class, ['record' => $account])
            ->assertActionHidden('resetPin');
    }

    public function test_account_view_page_renders_all_relation_managers(): void
    {
        $account = Account::create(['phone_number' => '+221770000052']);

        Livewire::actingAs($this->admin)
            ->test(ViewAccount::class, ['record' => $account->getRouteKey()])
            ->assertOk();
    }

    public function test_account_view_page_shows_the_active_ephemeral_card_status_without_its_number(): void
    {
        $account = Account::create(['phone_number' => '+221770000059']);
        $account->ephemeralCard()->create([
            'number' => '4111111111111111', 'cvv' => '987',
            'expiry_month' => 6, 'expiry_year' => 2027, 'balance_xof' => 7500,
        ]);

        $response = Livewire::actingAs($this->admin)
            ->test(ViewAccount::class, ['record' => $account->getRouteKey()])
            ->assertOk();

        $html = $response->html();
        $this->assertStringContainsString('7 500', $html);
        $this->assertStringContainsString('06/2027', $html);
        $this->assertStringNotContainsString('4111111111111111', $html);
        $this->assertStringNotContainsString('987', $html);
    }

    public function test_account_view_page_shows_no_ephemeral_card_message_when_none_active(): void
    {
        $account = Account::create(['phone_number' => '+221770000060']);

        $response = Livewire::actingAs($this->admin)
            ->test(ViewAccount::class, ['record' => $account->getRouteKey()])
            ->assertOk();

        $this->assertStringContainsString('Aucune carte éphémère active', $response->html());
    }

    public function test_cards_relation_manager_can_freeze_a_card(): void
    {
        $account = Account::create(['phone_number' => '+221770000053']);
        $card = $account->cards()->create([
            'last4' => '4242', 'expiry_month' => 9, 'expiry_year' => 2028, 'balance_xof' => 5000,
        ]);

        Livewire::actingAs($this->admin)
            ->test(CardsRelationManager::class, ['ownerRecord' => $account, 'pageClass' => ViewAccount::class])
            ->callTableAction('freeze', $card);

        $this->assertSame('frozen', $card->fresh()->status->value);
    }

    public function test_cards_relation_manager_can_cancel_a_card(): void
    {
        $account = Account::create(['phone_number' => '+221770000057']);
        $card = $account->cards()->create([
            'last4' => '4242', 'expiry_month' => 9, 'expiry_year' => 2028, 'balance_xof' => 5000,
        ]);

        Livewire::actingAs($this->admin)
            ->test(CardsRelationManager::class, ['ownerRecord' => $account, 'pageClass' => ViewAccount::class])
            ->callTableAction('cancel', $card);

        $this->assertSame(5000, $account->fresh()->balance_xof);
        $this->assertNull($account->cards()->find($card->id));
    }

    public function test_cards_relation_manager_can_edit_limits(): void
    {
        $account = Account::create(['phone_number' => '+221770000058']);
        $card = $account->cards()->create(['last4' => '4242', 'expiry_month' => 9, 'expiry_year' => 2028]);

        Livewire::actingAs($this->admin)
            ->test(CardsRelationManager::class, ['ownerRecord' => $account, 'pageClass' => ViewAccount::class])
            ->callTableAction('editLimits', $card, data: ['daily_limit_xof' => 100000, 'monthly_limit_xof' => 800000]);

        $card->refresh();
        $this->assertSame(100000, $card->daily_limit_xof);
        $this->assertSame(800000, $card->monthly_limit_xof);
    }

    public function test_pockets_relation_manager_can_unlock_a_pocket(): void
    {
        $account = Account::create(['phone_number' => '+221770000054']);
        $pocket = $account->pockets()->create(['name' => 'Coffre', 'locked_until' => now()->addDay()]);

        Livewire::actingAs($this->admin)
            ->test(PocketsRelationManager::class, ['ownerRecord' => $account, 'pageClass' => ViewAccount::class])
            ->callTableAction('unlock', $pocket);

        $this->assertNull($pocket->fresh()->locked_until);
    }

    public function test_foreign_balances_relation_manager_lists_the_clients_sub_accounts(): void
    {
        $account = Account::create(['phone_number' => '+221770000055']);
        $account->foreignBalances()->create(['currency_code' => 'EUR', 'amount_minor_units' => 1000]);

        Livewire::actingAs($this->admin)
            ->test(ForeignBalancesRelationManager::class, ['ownerRecord' => $account, 'pageClass' => ViewAccount::class])
            ->assertCanSeeTableRecords($account->foreignBalances);
    }

    public function test_beneficiaries_relation_manager_lists_and_can_delete(): void
    {
        $account = Account::create(['phone_number' => '+221770000056']);
        $beneficiary = $account->beneficiaries()->create(['name' => 'Awa Diallo', 'phone_number' => '+221771112233']);

        Livewire::actingAs($this->admin)
            ->test(BeneficiariesRelationManager::class, ['ownerRecord' => $account, 'pageClass' => ViewAccount::class])
            ->assertCanSeeTableRecords([$beneficiary])
            ->callTableAction('delete', $beneficiary);

        $this->assertNull($account->beneficiaries()->find($beneficiary->id));
    }

    public function test_bill_accounts_relation_manager_lists_and_can_delete(): void
    {
        $account = Account::create(['phone_number' => '+221770000061']);
        $billAccount = $account->billAccounts()->create([
            'bill_provider_type' => 'woyofal', 'nickname' => 'Maison', 'account_number' => '7042194024',
        ]);

        Livewire::actingAs($this->admin)
            ->test(BillAccountsRelationManager::class, ['ownerRecord' => $account, 'pageClass' => ViewAccount::class])
            ->assertCanSeeTableRecords([$billAccount])
            ->callTableAction('delete', $billAccount);

        $this->assertNull($account->billAccounts()->find($billAccount->id));
    }

    public function test_can_block_an_account_permanently(): void
    {
        $account = Account::create(['phone_number' => '+221770000062']);

        Livewire::actingAs($this->admin)
            ->test(AccountActionsWidget::class, ['record' => $account])
            ->callAction('blockPermanently', data: ['block_reason' => 'Fraude signalée']);

        $account->refresh();
        $this->assertTrue($account->isBlockedPermanently());
        $this->assertSame('Fraude signalée', $account->block_reason);
    }

    public function test_can_block_an_account_temporarily(): void
    {
        $account = Account::create(['phone_number' => '+221770000063']);
        $until = now()->addDays(3);

        Livewire::actingAs($this->admin)
            ->test(AccountActionsWidget::class, ['record' => $account])
            ->callAction('blockTemporarily', data: [
                'blocked_until' => $until,
                'block_reason' => 'Vérification en cours',
            ]);

        $account->refresh();
        $this->assertTrue($account->isBlockedTemporarily());
        $this->assertFalse($account->isBlockedPermanently());
    }

    public function test_can_unblock_an_account(): void
    {
        $account = Account::create(['phone_number' => '+221770000064']);
        $account->blocked_at = now();
        $account->block_reason = 'Test';
        $account->save();

        Livewire::actingAs($this->admin)
            ->test(AccountActionsWidget::class, ['record' => $account])
            ->callAction('unblock');

        $account->refresh();
        $this->assertFalse($account->isBlocked());
        $this->assertNull($account->block_reason);
    }

    public function test_block_actions_are_hidden_once_already_blocked_and_unblock_only_then(): void
    {
        $blocked = Account::create(['phone_number' => '+221770000065']);
        $blocked->blocked_at = now();
        $blocked->save();
        $notBlocked = Account::create(['phone_number' => '+221770000066']);

        Livewire::actingAs($this->admin)
            ->test(AccountActionsWidget::class, ['record' => $blocked])
            ->assertActionHidden('blockPermanently')
            ->assertActionHidden('blockTemporarily')
            ->assertActionVisible('unblock');

        Livewire::actingAs($this->admin)
            ->test(AccountActionsWidget::class, ['record' => $notBlocked])
            ->assertActionVisible('blockPermanently')
            ->assertActionVisible('blockTemporarily')
            ->assertActionHidden('unblock');
    }

    public function test_account_view_page_renders_the_actions_widget_in_its_footer(): void
    {
        $account = Account::create(['phone_number' => '+221770000067']);

        // Les widgets sont chargés paresseusement par défaut (Livewire
        // `lazy`) : la page ne rend qu'un placeholder au premier chargement,
        // pas le contenu du widget lui-même (voir « Sécurité du compte » —
        // couvert directement par les tests `AccountActionsWidget`
        // ci-dessus). On vérifie ici seulement que le widget est bien
        // enregistré et monté sur la page.
        $response = Livewire::actingAs($this->admin)
            ->test(ViewAccount::class, ['record' => $account->getRouteKey()])
            ->assertOk();

        $this->assertStringContainsString('account-actions-widget', $response->html());
    }
}

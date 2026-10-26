<?php

namespace Tests\Feature;

use App\Filament\Resources\AccountResource\Pages\ListAccounts;
use App\Filament\Resources\ChatMessageResource\Pages\ListChatMessages;
use App\Filament\Resources\TransactionResource\Pages\ListTransactions;
use App\Models\Account;
use App\Models\MobileMoneyProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

// Couvre les outils de résolution ajoutés à la demande du produit :
// ajustement de solde tracé, résolution/inversion de transaction, réponse
// support, et identifiants API des catalogues (chiffrés, jamais effacés
// par un champ laissé vide).
class AdminResolutionActionsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = tap(User::factory()->create(), fn ($u) => $u->assignRole(Role::findOrCreate('super_admin')));
    }

    public function test_no_balance_adjustment_action_exists_on_accounts(): void
    {
        // Verrou produit : « personne ne doit pouvoir modifier le solde d'un
        // compte » — l'action libre d'ajustement a été retirée, il ne reste
        // aucun moyen de modifier `balance_xof` depuis l'admin en dehors
        // d'une vraie transaction (résolution/inversion, voir plus bas).
        $account = Account::create(['phone_number' => '+221770000001']);

        Livewire::actingAs($this->admin)
            ->test(ListAccounts::class)
            ->assertTableActionDoesNotExist('adjustBalance', record: $account);
    }

    public function test_resolve_pending_transaction_as_completed_applies_balance(): void
    {
        $account = Account::create(['phone_number' => '+221770000003']);
        $account->balance_xof = 0;
        $account->save();

        $tx = $account->transactions()->create([
            'type' => 'transferIn',
            'status' => 'pending',
            'amount_xof' => 20000,
            'reference' => 'TX-PENDING-1',
        ]);

        Livewire::actingAs($this->admin)
            ->test(ListTransactions::class)
            ->callTableAction('resolve', $tx, data: [
                'outcome' => 'completed',
                'reason' => 'Confirmé manuellement après vérification opérateur',
            ]);

        $this->assertSame('completed', $tx->fresh()->status->value);
        $this->assertSame(20000, $account->fresh()->balance_xof);
    }

    public function test_resolve_pending_transaction_as_failed_does_not_touch_balance(): void
    {
        $account = Account::create(['phone_number' => '+221770000004']);
        $account->balance_xof = 0;
        $account->save();

        $tx = $account->transactions()->create([
            'type' => 'transferIn',
            'status' => 'pending',
            'amount_xof' => 20000,
            'reference' => 'TX-PENDING-2',
        ]);

        Livewire::actingAs($this->admin)
            ->test(ListTransactions::class)
            ->callTableAction('resolve', $tx, data: [
                'outcome' => 'failed',
                'reason' => 'Opérateur injoignable',
            ]);

        $this->assertSame('failed', $tx->fresh()->status->value);
        $this->assertSame('Opérateur injoignable', $tx->fresh()->failure_reason);
        $this->assertSame(0, $account->fresh()->balance_xof);
    }

    public function test_reverse_completed_transaction_credits_back_the_account(): void
    {
        // Solde réaliste : un vrai transferOut de 10000 n'aurait pu se
        // produire qu'avec au moins 10000 disponibles au départ (le solde
        // ne peut pas passer sous zéro, voir la contrainte en base ajoutée
        // dans `add_non_negative_balance_constraints`) — ici, 10000 avant,
        // 0 après le débit déjà appliqué.
        $account = Account::create(['phone_number' => '+221770000005']);
        $account->balance_xof = 10000;
        $account->save();

        $tx = $account->transactions()->create([
            'type' => 'transferOut',
            'status' => 'completed',
            'amount_xof' => -10000,
            'reference' => 'TX-COMPLETED-1',
        ]);
        $account->balance_xof = 0;
        $account->save();

        Livewire::actingAs($this->admin)
            ->test(ListTransactions::class)
            ->callTableAction('reverse', $tx, data: [
                'reason' => 'Litige client — transfert non reçu par le destinataire',
            ]);

        $this->assertSame('reversed', $tx->fresh()->status->value);
        $this->assertSame(10000, $account->fresh()->balance_xof);
    }

    public function test_reversing_one_leg_of_a_p2p_transfer_also_reverses_the_counterpart(): void
    {
        // Reproduit les deux écritures créées par `TransferController::
        // sendP2p()` : référence partagée, `-R` chez le destinataire.
        $sender = Account::create(['phone_number' => '+221770000020']);
        $sender->balance_xof = 0;
        $sender->save();
        $recipient = Account::create(['phone_number' => '+221770000021']);
        $recipient->balance_xof = 5000;
        $recipient->save();

        $senderTx = $sender->transactions()->create([
            'type' => 'transferOut', 'status' => 'completed', 'amount_xof' => -5000,
            'reference' => 'TRF-RBAC1',
        ]);
        $recipient->transactions()->create([
            'type' => 'transferIn', 'status' => 'completed', 'amount_xof' => 5000,
            'reference' => 'TRF-RBAC1-R',
        ]);

        Livewire::actingAs($this->admin)
            ->test(ListTransactions::class)
            ->callTableAction('reverse', $senderTx, data: [
                'reason' => 'Litige client — transfert contesté',
            ]);

        // Avant la correction : seul l'émetteur était recrédité, le
        // destinataire gardait la somme reçue (argent dupliqué).
        $this->assertSame(5000, $sender->fresh()->balance_xof);
        $this->assertSame(0, $recipient->fresh()->balance_xof);
        $this->assertSame('reversed', $senderTx->fresh()->status->value);
        $this->assertSame('reversed', $recipient->transactions()->first()->status->value);
    }

    public function test_reversing_a_p2p_transfer_is_blocked_when_the_recipient_already_spent_the_money(): void
    {
        $sender = Account::create(['phone_number' => '+221770000022']);
        $sender->balance_xof = 0;
        $sender->save();
        $recipient = Account::create(['phone_number' => '+221770000023']);
        $recipient->balance_xof = 1000;
        $recipient->save();

        $senderTx = $sender->transactions()->create([
            'type' => 'transferOut', 'status' => 'completed', 'amount_xof' => -5000,
            'reference' => 'TRF-RBAC2',
        ]);
        $recipient->transactions()->create([
            'type' => 'transferIn', 'status' => 'completed', 'amount_xof' => 5000,
            'reference' => 'TRF-RBAC2-R',
        ]);

        Livewire::actingAs($this->admin)
            ->test(ListTransactions::class)
            ->callTableAction('reverse', $senderTx, data: [
                'reason' => 'Litige client — transfert contesté',
            ]);

        // Ni l'un ni l'autre solde ne doit bouger : une inversion partielle
        // (créditer l'émetteur sans pouvoir redébiter le destinataire)
        // serait pire qu'aucune inversion (CLAUDE.md Phase C : jamais de
        // débit/crédit partiel en cas d'échec).
        $this->assertSame(0, $sender->fresh()->balance_xof);
        $this->assertSame(1000, $recipient->fresh()->balance_xof);
        $this->assertSame('completed', $senderTx->fresh()->status->value);
    }

    public function test_reply_action_creates_assistant_message_on_same_account(): void
    {
        $account = Account::create(['phone_number' => '+221770000006']);
        $userMessage = $account->chatMessages()->create(['role' => 'user', 'text' => 'Besoin d\'aide']);

        Livewire::actingAs($this->admin)
            ->test(ListChatMessages::class)
            ->callTableAction('reply', $userMessage, data: [
                'text' => 'Bonjour, comment puis-je vous aider ?',
            ]);

        $reply = $account->chatMessages()->latest()->first();
        $this->assertSame('assistant', $reply->role->value);
        $this->assertSame('Bonjour, comment puis-je vous aider ?', $reply->text);
    }

    public function test_provider_api_credentials_are_encrypted_and_kept_when_left_blank(): void
    {
        $provider = MobileMoneyProvider::create([
            'name' => 'Orange Money',
            'min_amount_xof' => 500,
            'max_amount_xof' => 2000000,
            'flow' => 'ussd',
            'country_dial_code' => '+221',
            'currency_code' => 'XOF',
            'api_key' => 'secret-key-123',
        ]);

        $this->assertTrue($provider->isConfigured());
        $raw = DB::table('mobile_money_providers')->where('id', $provider->id)->value('api_key');
        $this->assertNotSame('secret-key-123', $raw);
        $this->assertSame('secret-key-123', $provider->fresh()->api_key);

        // Laisser le champ vide à l'édition ne doit pas effacer le secret
        // déjà enregistré (voir `dehydrated()` sur ApiCredentialsFormSection).
        $provider->name = 'Orange Money SN';
        $provider->save();
        $this->assertSame('secret-key-123', $provider->fresh()->api_key);
    }
}

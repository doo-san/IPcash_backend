<?php

namespace Tests\Feature;

use App\Filament\Resources\AccountResource\Widgets\AccountActionsWidget;
use App\Filament\Resources\TransactionResource\Pages\ListTransactions;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

// Exigence produit : « toute modification doit avoir une traçabilité ».
// Couvre `LogsAdminActivity` : chaque action admin doit laisser une entrée
// dans le journal d'audit (qui, quoi, quand), et le trafic normal de l'API
// mobile (un client qui modifie son propre profil) ne doit jamais y
// apparaître — ce n'est pas une « modification admin ».
class AdminActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
    }

    public function test_the_audit_log_page_renders(): void
    {
        $this->actingAs($this->admin)->get('/admin/activity-logs')->assertOk();
    }

    public function test_unlocking_an_account_is_logged_with_the_causer(): void
    {
        $account = Account::create(['phone_number' => '+221770000010']);
        $account->failed_pin_attempts = 3;
        $account->locked_until = now()->addHour();
        $account->save();

        Livewire::actingAs($this->admin)
            ->test(AccountActionsWidget::class, ['record' => $account])
            ->callAction('unlock');

        $activity = Activity::query()->latest('id')->first();

        $this->assertNotNull($activity);
        $this->assertSame($this->admin->id, $activity->causer_id);
        $this->assertSame(Account::class, $activity->subject_type);
        $this->assertSame($account->id, $activity->subject_id);
        $this->assertSame('updated', $activity->event);
        $this->assertArrayHasKey('locked_until', $activity->properties['attributes'] ?? []);
    }

    public function test_resolving_a_transaction_logs_the_balance_change_on_the_account(): void
    {
        $account = Account::create(['phone_number' => '+221770000011']);
        $tx = $account->transactions()->create([
            'type' => 'transferIn', 'status' => 'pending', 'amount_xof' => 15000, 'reference' => 'TX-LOG-1',
        ]);

        Livewire::actingAs($this->admin)
            ->test(ListTransactions::class)
            ->callTableAction('resolve', $tx, data: [
                'outcome' => 'completed',
                'reason' => 'Confirmé manuellement',
            ]);

        $accountActivity = Activity::query()
            ->where('subject_type', Account::class)
            ->where('subject_id', $account->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($accountActivity, 'Le crédit du solde déclenché par la résolution doit être tracé.');
        $this->assertSame($this->admin->id, $accountActivity->causer_id);
        $this->assertSame(15000, $accountActivity->properties['attributes']['balance_xof'] ?? null);

        $txActivity = Activity::query()
            ->where('subject_type', $tx::class)
            ->where('subject_id', $tx->id)
            ->latest('id')
            ->first();
        $this->assertNotNull($txActivity, 'Le changement de statut de la transaction doit aussi être tracé.');
    }

    public function test_a_customer_updating_their_own_profile_via_the_api_is_never_logged(): void
    {
        $account = Account::create(['phone_number' => '+221770000012']);

        $this->patchJson('/api/profile', [
            'fullName' => 'Nouveau Nom',
        ], ['Authorization' => 'Bearer '.$account->createToken('test')->plainTextToken])->assertOk();

        $this->assertSame(
            0,
            Activity::query()->where('subject_type', Account::class)->where('subject_id', $account->id)->count(),
            "Une modification faite par le client lui-même via l'API n'est pas une « modification admin »."
        );
    }

    public function test_sensitive_columns_are_never_written_to_the_audit_log(): void
    {
        // Écriture directe (pas via une action Filament dédiée) pour vérifier
        // le filtre `$hidden` de `LogsAdminActivity` lui-même, indépendamment
        // de ce qu'une action donnée touche ou non aujourd'hui.
        $this->actingAs($this->admin);

        $account = Account::create(['phone_number' => '+221770000013']);
        $account->pin_hash = 'super-secret-hash';
        $account->save();

        $activities = Activity::query()->where('subject_type', Account::class)->where('subject_id', $account->id)->get();
        $this->assertNotEmpty($activities);

        foreach ($activities as $activity) {
            $this->assertArrayNotHasKey('pin_hash', $activity->properties['attributes'] ?? []);
            $this->assertArrayNotHasKey('pin_hash', $activity->properties['old'] ?? []);
        }
    }
}

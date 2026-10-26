<?php

namespace Tests\Feature;

use App\Filament\Resources\KycDocumentResource\Pages\ListKycDocuments;
use App\Filament\Resources\TransactionResource\Pages\ListTransactions;
use App\Models\Account;
use App\Models\FeeRule;
use App\Models\MobileMoneyProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

// Couvre le contrôle d'accès admin minimal ajouté suite à l'audit
// sécurité : (1) un compte `users` sans rôle n'entre plus du tout dans le
// panneau (avant : `canAccessPanel()` renvoyait toujours `true`) ; (2) le
// rôle `support` peut consulter mais pas déclencher les actions qui
// déplacent de l'argent ou valident une identité, ni modifier la
// configuration financière — seul `super_admin` le peut.
class AdminRoleAccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_without_any_role_cannot_access_the_panel(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_a_support_user_can_view_the_dashboard(): void
    {
        $support = tap(User::factory()->create(), fn ($u) => $u->assignRole(Role::findOrCreate('support')));

        $this->actingAs($support)->get('/admin')->assertOk();
    }

    public function test_support_cannot_resolve_or_reverse_a_transaction(): void
    {
        $support = tap(User::factory()->create(), fn ($u) => $u->assignRole(Role::findOrCreate('support')));
        $account = Account::create(['phone_number' => '+221770000010']);
        $account->balance_xof = 10000;
        $account->save();

        $pending = $account->transactions()->create([
            'type' => 'transferIn',
            'status' => 'pending',
            'amount_xof' => 5000,
            'reference' => 'TX-RBAC-PENDING',
        ]);
        $completed = $account->transactions()->create([
            'type' => 'transferOut',
            'status' => 'completed',
            'amount_xof' => -5000,
            'reference' => 'TX-RBAC-COMPLETED',
        ]);

        $component = Livewire::actingAs($support)->test(ListTransactions::class);
        $component->assertTableActionDoesNotExist('resolve', record: $pending);
        $component->assertTableActionDoesNotExist('reverse', record: $completed);

        $this->assertSame(10000, $account->fresh()->balance_xof);
    }

    public function test_support_cannot_approve_or_reject_a_kyc_document(): void
    {
        $support = tap(User::factory()->create(), fn ($u) => $u->assignRole(Role::findOrCreate('support')));
        $account = Account::create(['phone_number' => '+221770000011']);
        $document = $account->kycDocuments()->create([
            'document_type' => 'nationalId',
            'front_path' => 'kyc/fake/front.jpg',
            'selfie_path' => 'kyc/fake/selfie.jpg',
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        $component = Livewire::actingAs($support)->test(ListKycDocuments::class);
        $component->assertTableActionDoesNotExist('approve', record: $document);
        $component->assertTableActionDoesNotExist('reject', record: $document);

        $this->assertSame('pending', $document->fresh()->status->value);
    }

    public function test_support_cannot_edit_a_fee_rule_or_a_mobile_money_provider(): void
    {
        $support = tap(User::factory()->create(), fn ($u) => $u->assignRole(Role::findOrCreate('support')));
        $feeRule = FeeRule::create([
            'scope' => 'p2pTransfer', 'type' => 'percent', 'value' => 50, 'is_active' => true,
        ]);
        $provider = MobileMoneyProvider::create([
            'name' => 'Orange Money', 'min_amount_xof' => 500, 'max_amount_xof' => 2000000,
            'flow' => 'ussd', 'country_dial_code' => '+221', 'currency_code' => 'XOF',
        ]);

        $this->actingAs($support)->get("/admin/fee-rules/{$feeRule->id}/edit")->assertForbidden();
        $this->actingAs($support)->get('/admin/fee-rules/create')->assertForbidden();
        $this->actingAs($support)->get("/admin/mobile-money-providers/{$provider->id}/edit")->assertForbidden();
    }

    public function test_super_admin_retains_full_access(): void
    {
        $admin = tap(User::factory()->create(), fn ($u) => $u->assignRole(Role::findOrCreate('super_admin')));
        $feeRule = FeeRule::create([
            'scope' => 'p2pTransfer', 'type' => 'percent', 'value' => 50, 'is_active' => true,
        ]);

        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($admin)->get("/admin/fee-rules/{$feeRule->id}/edit")->assertOk();
    }
}

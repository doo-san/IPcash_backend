<?php

namespace Tests\Feature;

use App\Enums\InsurancePolicyStatus;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Filament\Resources\InsurancePolicyResource\Pages\ListInsurancePolicies;
use App\Models\Account;
use App\Models\AppSetting;
use App\Models\InsurancePolicy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminInsurancePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('insurance');
    }

    private function user(string $role): User
    {
        return tap(User::factory()->create(), fn ($u) => $u->assignRole(Role::findOrCreate($role)));
    }

    private function pendingPolicy(): InsurancePolicy
    {
        $account = Account::create(['phone_number' => '+221770009001']);
        $account->balance_xof = 0;
        $account->save();
        $tx = $account->transactions()->create([
            'type' => TransactionType::InsurancePurchase,
            'status' => TransactionStatus::Completed,
            'amount_xof' => -25000,
            'reference' => 'INSUR-ADMIN1',
        ]);

        $policy = new InsurancePolicy([
            'insurance_type' => 'auto', 'duration_months' => 6, 'price_xof' => 25000,
            'plate_number' => 'DK1234AB', 'vehicle_usage' => 'personal',
            'horsepower' => 7, 'seats' => 5, 'age_bracket' => 'fiveToTen',
        ]);
        $policy->account_id = $account->id;
        $policy->status = InsurancePolicyStatus::Pending;
        $policy->purchase_transaction_id = $tx->id;
        $policy->save();

        return $policy;
    }

    public function test_the_policy_pages_load(): void
    {
        $policy = $this->pendingPolicy();
        $admin = $this->user('super_admin');

        $this->actingAs($admin)->get('/admin/insurance-policies')->assertOk();
        $this->actingAs($admin)->get("/admin/insurance-policies/{$policy->id}")->assertOk();
    }

    public function test_super_admin_cannot_approve_while_mentions_are_missing(): void
    {
        $policy = $this->pendingPolicy();

        Livewire::actingAs($this->user('super_admin'))
            ->test(ListInsurancePolicies::class)
            ->callTableAction('approve', $policy)
            ->assertNotified('Mentions de l\'attestation incomplètes');

        $this->assertSame('pending', $policy->fresh()->status->value);
    }

    public function test_super_admin_approves_and_an_attestation_is_issued(): void
    {
        foreach (['insurance_insurer_name', 'insurance_intermediary_name', 'insurance_agreement_number', 'insurance_guarantees'] as $key) {
            AppSetting::create(['key' => $key, 'value' => 'Valeur '.$key]);
        }
        $policy = $this->pendingPolicy();

        Livewire::actingAs($this->user('super_admin'))
            ->test(ListInsurancePolicies::class)
            ->callTableAction('approve', $policy);

        $policy->refresh();
        $this->assertSame('active', $policy->status->value);
        Storage::disk('insurance')->assertExists($policy->attestation_path);
    }

    public function test_super_admin_rejects_and_the_client_is_refunded(): void
    {
        $policy = $this->pendingPolicy();

        Livewire::actingAs($this->user('super_admin'))
            ->test(ListInsurancePolicies::class)
            ->callTableAction('reject', $policy, data: ['reason' => 'Plaque illisible']);

        $this->assertSame('rejected', $policy->fresh()->status->value);
        $this->assertSame(25000, $policy->account->fresh()->balance_xof);
    }

    public function test_support_can_list_but_not_decide(): void
    {
        $policy = $this->pendingPolicy();

        Livewire::actingAs($this->user('support'))
            ->test(ListInsurancePolicies::class)
            ->assertTableActionDoesNotExist('approve', record: $policy)
            ->assertTableActionDoesNotExist('reject', record: $policy);

        $this->assertSame('pending', $policy->fresh()->status->value);
    }

    public function test_support_cannot_open_the_settings_pages(): void
    {
        $support = $this->user('support');

        $this->actingAs($support)->get('/admin/app-config-page')->assertForbidden();
        $this->actingAs($support)->get('/admin/site-footer-page')->assertForbidden();
    }
}

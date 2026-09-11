<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\ChatbotAnswer;
use App\Models\Country;
use App\Models\FaqEntry;
use App\Models\FeeRule;
use App\Models\FinancialInstitution;
use App\Models\PromoCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Test de fumée pour les catalogues ajoutés lors de l'audit des parties de
// l'app encore absentes de l'admin — voir chaque migration pour le détail
// de ce que chaque table remplace côté Flutter.
class AdminCatalogResourcesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
    }

    public function test_fee_rule_pages_load_and_percent_fee_computation_is_correct(): void
    {
        $rule = FeeRule::create(['scope' => 'p2pTransfer', 'type' => 'percent', 'value' => 50]);

        $this->actingAs($this->admin)->get('/admin/fee-rules')->assertOk();
        $this->actingAs($this->admin)->get('/admin/fee-rules/create')->assertOk();
        $this->actingAs($this->admin)->get("/admin/fee-rules/{$rule->id}/edit")->assertOk();

        // 0,5 % de 10 000 F = 50 F, comme _feeRateBasisPoints côté Flutter.
        $this->assertSame(50, $rule->feeFor(10000));
    }

    public function test_fee_rule_respects_min_and_max_bounds(): void
    {
        $rule = FeeRule::create([
            'scope' => 'cashIn', 'type' => 'percent', 'value' => 50,
            'min_fee_xof' => 100, 'max_fee_xof' => 500,
        ]);

        $this->assertSame(100, $rule->feeFor(1000)); // 5F théorique -> plancher 100
        $this->assertSame(500, $rule->feeFor(1000000)); // 5000F théorique -> plafond 500
    }

    public function test_financial_institution_pages_load(): void
    {
        $institution = FinancialInstitution::create(['name' => 'CBAO', 'type' => 'bank']);

        $this->actingAs($this->admin)->get('/admin/financial-institutions')->assertOk();
        $this->actingAs($this->admin)->get('/admin/financial-institutions/create')->assertOk();
        $this->actingAs($this->admin)->get("/admin/financial-institutions/{$institution->id}/edit")->assertOk();
    }

    public function test_faq_entry_pages_load(): void
    {
        $entry = FaqEntry::create(['question' => 'Comment recharger ?', 'answer' => 'Via mobile money.']);

        $this->actingAs($this->admin)->get('/admin/faq-entries')->assertOk();
        $this->actingAs($this->admin)->get('/admin/faq-entries/create')->assertOk();
        $this->actingAs($this->admin)->get("/admin/faq-entries/{$entry->id}/edit")->assertOk();
    }

    public function test_chatbot_answer_pages_load(): void
    {
        $answer = ChatbotAnswer::create([
            'keywords' => ['carte', 'carte prépayée'],
            'answer' => 'Rendez-vous dans l\'onglet Carte.',
        ]);

        $this->actingAs($this->admin)->get('/admin/chatbot-answers')->assertOk();
        $this->actingAs($this->admin)->get('/admin/chatbot-answers/create')->assertOk();
        $this->actingAs($this->admin)->get("/admin/chatbot-answers/{$answer->id}/edit")->assertOk();
    }

    public function test_app_setting_pages_load(): void
    {
        $setting = AppSetting::create(['key' => 'support_phone_number', 'value' => '+221338000000']);

        $this->actingAs($this->admin)->get('/admin/app-settings')->assertOk();
        $this->actingAs($this->admin)->get('/admin/app-settings/create')->assertOk();
        $this->actingAs($this->admin)->get("/admin/app-settings/{$setting->key}/edit")->assertOk();

        $this->assertSame('+221338000000', AppSetting::get('support_phone_number'));
        $this->assertSame('default', AppSetting::get('unknown_key', 'default'));
    }

    public function test_promo_code_pages_load_and_validity_logic(): void
    {
        $code = PromoCode::create([
            'code' => 'BIENVENUE10', 'discount_type' => 'percent', 'discount_value' => 1000,
            'max_redemptions' => 1, 'redemptions_count' => 0,
        ]);

        $this->actingAs($this->admin)->get('/admin/promo-codes')->assertOk();
        $this->actingAs($this->admin)->get('/admin/promo-codes/create')->assertOk();
        $this->actingAs($this->admin)->get("/admin/promo-codes/{$code->code}/edit")->assertOk();

        $this->assertTrue($code->isValidNow());

        $code->redemptions_count = 1;
        $code->save();
        $this->assertFalse($code->fresh()->isValidNow());
    }

    public function test_country_pages_load(): void
    {
        $country = Country::create([
            'dial_code' => '+221', 'name' => 'Sénégal', 'flag' => '🇸🇳',
            'min_digits' => 9, 'max_digits' => 9, 'currency_code' => 'XOF',
        ]);

        $this->actingAs($this->admin)->get('/admin/countries')->assertOk();
        $this->actingAs($this->admin)->get('/admin/countries/create')->assertOk();
        $this->actingAs($this->admin)->get("/admin/countries/{$country->dial_code}/edit")->assertOk();
    }
}

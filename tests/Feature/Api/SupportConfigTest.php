<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\AppSetting;
use App\Models\FaqEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// GET /support/config — expose le numéro de support édité dans l'admin
// (AppSettingResource), consommé par le bouton d'appel de
// `profile_screen.dart` côté app. Le champ `faq` reste dans la réponse
// pour le contrat (`api/openapi.yaml`) mais n'est plus affiché côté app —
// ces mêmes entrées (FaqEntryResource) alimentent désormais le chatbot IA
// (voir AiSupportChatService).
class SupportConfigTest extends TestCase
{
    use RefreshDatabase;

    private function account(): Account
    {
        return Account::create(['phone_number' => '+2217715'.random_int(10000, 99999)]);
    }

    private function authHeader(Account $account): array
    {
        return ['Authorization' => 'Bearer '.$account->createToken('test')->plainTextToken];
    }

    public function test_falls_back_to_the_default_phone_number_when_no_setting_exists(): void
    {
        $account = $this->account();

        $this->getJson('/api/support/config', $this->authHeader($account))
            ->assertOk()
            ->assertJson(['supportPhoneNumber' => '+221338000000', 'faq' => []]);
    }

    public function test_returns_the_admin_configured_phone_number_and_published_faq_entries(): void
    {
        $account = $this->account();

        AppSetting::create([
            'key' => 'support_phone_number',
            'value' => '+221770000099',
        ]);

        FaqEntry::create([
            'locale' => 'fr',
            'question' => 'Comment ça marche ?',
            'answer' => 'Très simplement.',
            'position' => 1,
            'is_published' => true,
        ]);
        FaqEntry::create([
            'locale' => 'fr',
            'question' => 'Brouillon non publié',
            'answer' => '…',
            'position' => 0,
            'is_published' => false,
        ]);
        FaqEntry::create([
            'locale' => 'en',
            'question' => 'How does it work?',
            'answer' => 'Very simply.',
            'position' => 0,
            'is_published' => true,
        ]);

        $response = $this->getJson('/api/support/config', $this->authHeader($account))
            ->assertOk()
            ->json();

        $this->assertSame('+221770000099', $response['supportPhoneNumber']);
        $this->assertCount(1, $response['faq']);
        $this->assertSame('Comment ça marche ?', $response['faq'][0]['question']);
    }
}

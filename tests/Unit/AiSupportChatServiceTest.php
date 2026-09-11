<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Services\AiSupportChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Sans clé Anthropic configurée (jamais le cas dans cet environnement de
// test), le service doit rester silencieusement indisponible plutôt que
// de lever une exception — c'est ce qui permet à `SupportController` de
// se replier sur l'ancien système mot-clé sans jamais casser le chat.
class AiSupportChatServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_unavailable_without_an_api_key(): void
    {
        config(['services.anthropic.key' => null]);

        $service = new AiSupportChatService;

        $this->assertFalse($service->isAvailable());
    }

    public function test_reply_returns_null_when_unavailable(): void
    {
        config(['services.anthropic.key' => null]);
        $account = Account::create(['phone_number' => '+221770000080']);

        $service = new AiSupportChatService;

        $this->assertNull($service->reply($account, 'Bonjour'));
    }
}

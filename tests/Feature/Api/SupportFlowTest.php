<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\ChatbotAnswer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportFlowTest extends TestCase
{
    use RefreshDatabase;

    private function authHeader(Account $account): array
    {
        return ['Authorization' => 'Bearer '.$account->createToken('test')->plainTextToken];
    }

    public function test_chat_matches_keyword_and_stores_both_messages(): void
    {
        ChatbotAnswer::create(['keywords' => ['frais', 'commission'], 'answer' => 'Les frais sont de 0,5 %.']);
        ChatbotAnswer::create(['is_fallback' => true, 'keywords' => [], 'answer' => 'Réponse par défaut.']);
        $account = Account::create(['phone_number' => '+221771234567']);

        $reply = $this->postJson('/api/support/chat', [
            'message' => 'Quels sont les frais sur un transfert ?',
        ], $this->authHeader($account))->assertOk()->json();

        $this->assertSame('assistant', $reply['role']);
        $this->assertSame('Les frais sont de 0,5 %.', $reply['text']);
        $this->assertSame(2, $account->chatMessages()->count());
        $this->assertSame('user', $account->chatMessages()->oldest()->first()->role->value);
    }

    public function test_chat_falls_back_when_no_keyword_matches(): void
    {
        ChatbotAnswer::create(['keywords' => ['frais'], 'answer' => 'Les frais sont de 0,5 %.']);
        ChatbotAnswer::create(['is_fallback' => true, 'keywords' => [], 'answer' => 'Réponse par défaut.']);
        $account = Account::create(['phone_number' => '+221771234567']);

        $reply = $this->postJson('/api/support/chat', [
            'message' => 'Bonjour, ceci ne correspond à aucun mot-clé',
        ], $this->authHeader($account))->assertOk()->json();

        $this->assertSame('Réponse par défaut.', $reply['text']);
    }
}

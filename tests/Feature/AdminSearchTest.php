<?php

namespace Tests\Feature;

use App\Filament\Resources\CardResource\Pages\ListCards;
use App\Filament\Resources\ChatbotAnswerResource\Pages\ListChatbotAnswers;
use App\Filament\Resources\KycDocumentResource\Pages\ListKycDocuments;
use App\Filament\Resources\TransactionResource\Pages\ListTransactions;
use App\Models\Account;
use App\Models\ChatbotAnswer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

// Exigence produit : « je veux des boutons de recherche » — chaque
// ressource qui n'avait aucune colonne `->searchable()` (donc aucune
// barre de recherche affichée) en a maintenant une.
class AdminSearchTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
    }

    public function test_cards_are_searchable_by_last4(): void
    {
        $account = Account::create(['phone_number' => '+221770000060']);
        $match = $account->cards()->create(['last4' => '1234', 'expiry_month' => 9, 'expiry_year' => 2028]);
        $other = $account->cards()->create(['last4' => '5678', 'expiry_month' => 9, 'expiry_year' => 2028]);

        Livewire::actingAs($this->admin)
            ->test(ListCards::class)
            ->searchTable('1234')
            ->assertCanSeeTableRecords([$match])
            ->assertCanNotSeeTableRecords([$other]);
    }

    public function test_kyc_documents_are_searchable_by_client_phone_number(): void
    {
        $matching = Account::create(['phone_number' => '+221770000061']);
        $other = Account::create(['phone_number' => '+221770000062']);
        $matchingDoc = $matching->kycDocuments()->create([
            'document_type' => 'nationalId', 'status' => 'pending', 'submitted_at' => now(),
            'front_path' => 'kyc/front-a.jpg', 'selfie_path' => 'kyc/selfie-a.jpg',
        ]);
        $otherDoc = $other->kycDocuments()->create([
            'document_type' => 'nationalId', 'status' => 'pending', 'submitted_at' => now(),
            'front_path' => 'kyc/front-b.jpg', 'selfie_path' => 'kyc/selfie-b.jpg',
        ]);

        Livewire::actingAs($this->admin)
            ->test(ListKycDocuments::class)
            ->searchTable('0000061')
            ->assertCanSeeTableRecords([$matchingDoc])
            ->assertCanNotSeeTableRecords([$otherDoc]);
    }

    public function test_transactions_are_searchable_by_client_phone_number(): void
    {
        $matching = Account::create(['phone_number' => '+221770000063']);
        $other = Account::create(['phone_number' => '+221770000064']);
        $matchingTx = $matching->transactions()->create(['type' => 'transferIn', 'status' => 'completed', 'amount_xof' => 1000, 'reference' => 'TX-A']);
        $otherTx = $other->transactions()->create(['type' => 'transferIn', 'status' => 'completed', 'amount_xof' => 1000, 'reference' => 'TX-B']);

        Livewire::actingAs($this->admin)
            ->test(ListTransactions::class)
            ->searchTable('0000063')
            ->assertCanSeeTableRecords([$matchingTx])
            ->assertCanNotSeeTableRecords([$otherTx]);
    }

    public function test_chatbot_answers_are_searchable_by_keyword(): void
    {
        $matching = ChatbotAnswer::create(['keywords' => ['carte', 'carte prépayée'], 'answer' => 'Rendez-vous dans l\'onglet Carte.']);
        $other = ChatbotAnswer::create(['keywords' => ['virement'], 'answer' => 'Utilisez le transfert P2P.']);

        Livewire::actingAs($this->admin)
            ->test(ListChatbotAnswers::class)
            ->searchTable('carte')
            ->assertCanSeeTableRecords([$matching])
            ->assertCanNotSeeTableRecords([$other]);
    }
}

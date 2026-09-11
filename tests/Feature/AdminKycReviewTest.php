<?php

namespace Tests\Feature;

use App\Filament\Resources\KycDocumentResource\Pages\ListKycDocuments;
use App\Filament\Resources\KycDocumentResource\Pages\ViewKycDocument;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

// Exigence produit : la revue KYC doit vraiment permettre de vérifier une
// identité — le nom déclaré à l'inscription doit être visible à côté du
// document pour que le réviseur puisse les comparer, sans naviguer vers
// la fiche client.
class AdminKycReviewTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
    }

    public function test_the_declared_name_is_searchable_in_the_review_queue(): void
    {
        $matching = Account::create(['phone_number' => '+221770000072', 'first_name' => 'Aminata', 'last_name' => 'Diop']);
        $other = Account::create(['phone_number' => '+221770000073', 'first_name' => 'Moussa', 'last_name' => 'Fall']);
        $matchingDoc = $matching->kycDocuments()->create([
            'document_type' => 'nationalId', 'front_path' => 'a.jpg', 'selfie_path' => 'b.jpg', 'submitted_at' => now(),
        ]);
        $otherDoc = $other->kycDocuments()->create([
            'document_type' => 'nationalId', 'front_path' => 'c.jpg', 'selfie_path' => 'd.jpg', 'submitted_at' => now(),
        ]);

        Livewire::actingAs($this->admin)
            ->test(ListKycDocuments::class)
            ->searchTable('Diop')
            ->assertCanSeeTableRecords([$matchingDoc])
            ->assertCanNotSeeTableRecords([$otherDoc]);
    }

    public function test_the_declared_name_appears_next_to_the_document_for_review(): void
    {
        $account = Account::create(['phone_number' => '+221770000074', 'first_name' => 'Aminata', 'last_name' => 'Diop']);
        $document = $account->kycDocuments()->create([
            'document_type' => 'nationalId', 'front_path' => 'a.jpg', 'selfie_path' => 'b.jpg', 'submitted_at' => now(),
        ]);

        Livewire::actingAs($this->admin)
            ->test(ViewKycDocument::class, ['record' => $document->getRouteKey()])
            ->assertSee('Aminata Diop');
    }
}

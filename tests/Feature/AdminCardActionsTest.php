<?php

namespace Tests\Feature;

use App\Filament\Resources\CardResource\Pages\EditCard;
use App\Filament\Resources\CardResource\Pages\ListCards;
use App\Models\Account;
use App\Models\Card;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

// Exigence produit : sur « Cartes », la liste n'affiche que les
// informations — les actions (geler/dégeler, bloquer/débloquer, supprimer)
// vivent sur la page « Modifier » (EditCard).
class AdminCardActionsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Account $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        $this->account = Account::create(['phone_number' => '+221770000090']);
    }

    private function card(string $status = 'active'): Card
    {
        return $this->account->cards()->create([
            'last4' => '4242', 'expiry_month' => 9, 'expiry_year' => 2028, 'status' => $status,
        ]);
    }

    public function test_the_list_only_exposes_the_edit_action(): void
    {
        $card = $this->card();

        Livewire::actingAs($this->admin)
            ->test(ListCards::class)
            ->assertTableActionExists('edit')
            ->assertTableActionDoesNotExist('freeze')
            ->assertTableActionDoesNotExist('deleteCard')
            ->assertTableActionDoesNotExist('block');
    }

    public function test_freeze_and_unfreeze_from_the_edit_page(): void
    {
        $card = $this->card();

        Livewire::actingAs($this->admin)
            ->test(EditCard::class, ['record' => $card->getRouteKey()])
            ->callAction('freeze');
        $this->assertSame('frozen', $card->fresh()->status->value);

        Livewire::actingAs($this->admin)
            ->test(EditCard::class, ['record' => $card->getRouteKey()])
            ->callAction('unfreeze');
        $this->assertSame('active', $card->fresh()->status->value);
    }

    public function test_block_and_unblock_from_the_edit_page(): void
    {
        $card = $this->card();

        Livewire::actingAs($this->admin)
            ->test(EditCard::class, ['record' => $card->getRouteKey()])
            ->callAction('block');
        $this->assertSame('blocked', $card->fresh()->status->value);

        Livewire::actingAs($this->admin)
            ->test(EditCard::class, ['record' => $card->getRouteKey()])
            ->callAction('unblock');
        $this->assertSame('active', $card->fresh()->status->value);
    }

    public function test_freeze_unfreeze_and_block_actions_toggle_visibility_with_status(): void
    {
        $active = $this->card('active');
        Livewire::actingAs($this->admin)
            ->test(EditCard::class, ['record' => $active->getRouteKey()])
            ->assertActionVisible('freeze')
            ->assertActionHidden('unfreeze')
            ->assertActionVisible('block')
            ->assertActionHidden('unblock');

        $blocked = $this->card('blocked');
        Livewire::actingAs($this->admin)
            ->test(EditCard::class, ['record' => $blocked->getRouteKey()])
            ->assertActionHidden('block')
            ->assertActionVisible('unblock')
            ->assertActionHidden('freeze');
    }
}

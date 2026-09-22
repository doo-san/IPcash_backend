<?php

namespace Tests\Feature;

use App\Filament\Resources\FeeRuleResource\Pages\CreateFeeRule;
use App\Filament\Resources\FeeRuleResource\Pages\EditFeeRule;
use App\Models\FeeRule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

// Le champ "Taux" d'une règle de frais en pourcentage se saisit en
// pourcentage normal (1 = 1 %) depuis l'admin, alors que `FeeRule.value`
// reste stocké en points de base (100 = 1 %, voir FeeRule::feeFor()) —
// aucun changement de colonne ni de calcul, seulement une conversion
// aller-retour sur le formulaire (FeeRuleResource).
class AdminFeeRulePercentInputTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
    }

    public function test_creating_a_percent_rule_with_1_stores_100_basis_points(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CreateFeeRule::class)
            ->fillForm(['scope' => 'p2pTransfer', 'type' => 'percent', 'value' => 1])
            ->call('create')
            ->assertHasNoFormErrors();

        $rule = FeeRule::where('scope', 'p2pTransfer')->firstOrFail();
        $this->assertSame(100, $rule->value);
        // Le calcul réel (FeeRule::feeFor) doit refléter 1 %, pas 1 point
        // de base (0,01 %).
        $this->assertSame(1000, $rule->feeFor(100000));
    }

    public function test_creating_a_fixed_rule_stores_the_amount_unchanged(): void
    {
        Livewire::actingAs($this->admin)
            ->test(CreateFeeRule::class)
            ->fillForm(['scope' => 'cashIn', 'type' => 'fixed', 'value' => 200])
            ->call('create')
            ->assertHasNoFormErrors();

        $rule = FeeRule::where('scope', 'cashIn')->firstOrFail();
        $this->assertSame(200, $rule->value);
    }

    public function test_editing_a_percent_rule_displays_it_back_as_a_normal_percentage(): void
    {
        $rule = FeeRule::create(['scope' => 'cashOut', 'type' => 'percent', 'value' => 250]); // 2,5 %

        Livewire::actingAs($this->admin)
            ->test(EditFeeRule::class, ['record' => $rule->id])
            ->assertFormSet(['value' => 2.5]);
    }
}

<?php

namespace Tests\Feature;

use App\Filament\Resources\BillProviderResource\Pages\EditBillProvider;
use App\Filament\Resources\BillProviderResource\RelationManagers\PlansRelationManager;
use App\Models\BillProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

// Exigence produit : « forfaits factures et fournisseurs de factures
// doivent être sur la même page » — plus de ressource « Forfaits
// factures » autonome, uniquement un onglet sur la fiche du fournisseur.
class AdminBillProviderPlansTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
    }

    public function test_can_create_edit_and_delete_a_plan_from_the_providers_page(): void
    {
        $provider = BillProvider::firstOrCreate(['type' => 'canalPlus'], ['name' => 'Canal+']);

        Livewire::actingAs($this->admin)
            ->test(PlansRelationManager::class, ['ownerRecord' => $provider, 'pageClass' => EditBillProvider::class])
            ->callTableAction('create', data: [
                'kind' => 'formula', 'code' => 'access', 'label' => 'Access', 'price_xof' => 5500,
            ]);

        $plan = $provider->plans()->first();
        $this->assertNotNull($plan);
        $this->assertSame('access', $plan->code);
        $this->assertSame(5500, $plan->price_xof);

        Livewire::actingAs($this->admin)
            ->test(PlansRelationManager::class, ['ownerRecord' => $provider, 'pageClass' => EditBillProvider::class])
            ->callTableAction('edit', $plan, data: [
                'kind' => 'formula', 'code' => 'access', 'label' => 'Access', 'price_xof' => 6000, 'is_active' => true,
            ]);
        $this->assertSame(6000, $plan->fresh()->price_xof);

        Livewire::actingAs($this->admin)
            ->test(PlansRelationManager::class, ['ownerRecord' => $provider, 'pageClass' => EditBillProvider::class])
            ->callTableAction('delete', $plan->fresh());

        $this->assertNull($provider->plans()->find($plan->id));
    }

    public function test_plans_are_scoped_to_their_provider(): void
    {
        $canalPlus = BillProvider::firstOrCreate(['type' => 'canalPlus'], ['name' => 'Canal+']);
        $woyofal = BillProvider::firstOrCreate(['type' => 'woyofal'], ['name' => 'Woyofal']);
        $canalPlusPlan = $canalPlus->plans()->create(['kind' => 'formula', 'code' => 'access', 'label' => 'Access', 'price_xof' => 5500]);
        $woyofalPlan = $woyofal->plans()->create(['kind' => 'option', 'code' => 'basic', 'label' => 'Basique', 'price_xof' => 1000]);

        Livewire::actingAs($this->admin)
            ->test(PlansRelationManager::class, ['ownerRecord' => $canalPlus, 'pageClass' => EditBillProvider::class])
            ->assertCanSeeTableRecords([$canalPlusPlan])
            ->assertCanNotSeeTableRecords([$woyofalPlan]);
    }
}

<?php

namespace Tests\Feature;

use App\Filament\Resources\EsimPlanResource\Pages\ListEsimPlans;
use App\Models\EsimPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

// Exigence produit : « pour esim plans je veux qu'on ait 3 onglets pour
// les 3 zones » — un onglet par valeur de `EsimScope`, à la place du
// filtre déroulant qu'ils remplacent.
class AdminEsimPlanTabsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
    }

    public function test_each_tab_shows_only_its_own_zone(): void
    {
        $local = EsimPlan::create(['scope' => 'local', 'data_gb' => 5, 'validity_days' => 30, 'price_xof' => 6500]);
        $regional = EsimPlan::create(['scope' => 'regional', 'data_gb' => 5, 'validity_days' => 30, 'price_xof' => 10400]);
        $global = EsimPlan::create(['scope' => 'global', 'data_gb' => 5, 'validity_days' => 30, 'price_xof' => 18200]);

        $component = Livewire::actingAs($this->admin)->test(ListEsimPlans::class);

        $component->set('activeTab', 'local')
            ->assertCanSeeTableRecords([$local])
            ->assertCanNotSeeTableRecords([$regional, $global]);

        $component->set('activeTab', 'regional')
            ->assertCanSeeTableRecords([$regional])
            ->assertCanNotSeeTableRecords([$local, $global]);

        $component->set('activeTab', 'global')
            ->assertCanSeeTableRecords([$global])
            ->assertCanNotSeeTableRecords([$local, $regional]);
    }
}

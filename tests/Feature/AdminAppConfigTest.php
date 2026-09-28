<?php

namespace Tests\Feature;

use App\Filament\Pages\AppConfigPage;
use App\Filament\Widgets\PendingWorkStatsWidget;
use App\Models\AppSetting;
use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

// Page "Réglages de l'application" (config/app_config.php) et widget
// "À traiter" du tableau de bord.
class AdminAppConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_page_renders_with_defaults(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->get('/admin/app-config-page')->assertOk()->assertSee('Réglages de l');

        Livewire::actingAs($admin)
            ->test(AppConfigPage::class)
            ->assertSet('data.transfer_min_amount_xof', 5)
            ->assertSet('data.feature_credit_enabled', true)
            ->assertSet('data.maintenance_enabled', false);
    }

    public function test_saving_changes_what_the_public_config_serves(): void
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(AppConfigPage::class)
            ->set('data.transfer_min_amount_xof', 50)
            ->set('data.feature_esim_enabled', false)
            ->set('data.maintenance_message', 'Retour bientôt')
            ->set('data.min_app_version_android', '2.0.0')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->getJson('/api/config')->assertJson([
            'limits' => ['transferMinAmountXof' => 50],
            'features' => ['esim' => false, 'credit' => true],
            'maintenance' => ['enabled' => false, 'message' => 'Retour bientôt'],
            'minAppVersion' => ['android' => '2.0.0'],
        ]);
        $this->assertSame('0', AppSetting::get('feature_esim_enabled'));
    }

    public function test_the_minimum_transfer_amount_cannot_be_zero(): void
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(AppConfigPage::class)
            ->set('data.transfer_min_amount_xof', 0)
            ->call('save')
            ->assertHasFormErrors(['transfer_min_amount_xof']);
    }

    public function test_saving_is_recorded_in_the_audit_log(): void
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(AppConfigPage::class)
            ->set('data.transfer_min_amount_xof', 25)
            ->call('save');

        $this->assertDatabaseHas('activity_log', ['subject_id' => 'transfer_min_amount_xof']);
    }

    public function test_the_dashboard_shows_the_pending_work_widget(): void
    {
        $admin = User::factory()->create();
        ContactMessage::create(['name' => 'A', 'email' => 'a@example.com', 'subject' => 'Question', 'message' => 'Bonjour']);

        $this->actingAs($admin)->get('/admin')->assertOk();

        Livewire::actingAs($admin)
            ->test(PendingWorkStatsWidget::class)
            ->assertSee('KYC à examiner')
            ->assertSee('Transactions en attente')
            ->assertSee('Messages non lus');
    }
}

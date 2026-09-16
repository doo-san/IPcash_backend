<?php

namespace Tests\Feature;

use App\Filament\Pages\SiteSettingsPage;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

// Réglages globaux du site public (liens réseaux sociaux, liens de
// téléchargement, images) depuis l'admin — voir config/site_settings.php,
// app/Support/helpers.php (site_setting()/site_setting_image_url()).
class AdminSiteSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->get('/admin/site-settings-page')->assertOk();
    }

    public function test_saving_a_social_link_makes_it_appear_in_the_footer(): void
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(SiteSettingsPage::class)
            ->set('data.links.social_linkedin', 'https://linkedin.com/company/ipcash')
            ->call('save');

        $this->assertSame(
            'https://linkedin.com/company/ipcash',
            SiteSetting::where('key', 'social_linkedin')->value('value'),
        );

        $this->get('/')->assertSee('https://linkedin.com/company/ipcash', false);
    }

    public function test_uploading_a_logo_overrides_the_default_asset(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(SiteSettingsPage::class)
            ->set('data.images.logo', UploadedFile::fake()->image('logo.png'))
            ->call('save');

        $stored = SiteSetting::where('key', 'logo')->value('value');
        $this->assertNotNull($stored);
        Storage::disk('public')->assertExists($stored);

        $this->assertSame(Storage::disk('public')->url($stored), site_setting_image_url('logo'));
    }

    public function test_without_any_override_the_default_asset_is_used(): void
    {
        $this->assertSame(asset('images/ipcash-icon.svg'), site_setting_image_url('logo'));
        $this->assertSame('', site_setting('social_linkedin'));
    }
}

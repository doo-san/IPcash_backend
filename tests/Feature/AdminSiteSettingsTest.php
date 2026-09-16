<?php

namespace Tests\Feature;

use App\Filament\Pages\SiteImagesPage;
use App\Filament\Pages\SiteLinksPage;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

// Réglages globaux du site public (liens réseaux sociaux, liens de
// téléchargement, images), répartis en deux sous-pages du groupe de
// navigation "Site public" — voir config/site_settings.php,
// app/Support/helpers.php (site_setting()/site_setting_image_url()).
class AdminSiteSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_both_pages_render(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->get('/admin/site-links-page')->assertOk();
        $this->actingAs($admin)->get('/admin/site-images-page')->assertOk();
    }

    public function test_saving_a_social_link_makes_it_appear_in_the_footer(): void
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(SiteLinksPage::class)
            ->set('data.social_linkedin', 'https://linkedin.com/company/ipcash')
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
            ->test(SiteImagesPage::class)
            ->set('data.logo', UploadedFile::fake()->image('logo.png'))
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

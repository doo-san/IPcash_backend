<?php

namespace Tests\Feature;

use App\Filament\Pages\SiteContentPage;
use App\Models\SiteContent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

// Édition des textes clés du site public depuis l'admin (voir
// config/site_content.php, app/Support/helpers.php::site_content()).
class AdminSiteContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_prefilled_with_current_defaults(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->get('/admin/site-content-page')->assertOk();

        Livewire::actingAs($admin)
            ->test(SiteContentPage::class)
            ->assertSet('data.home.features_heading', 'Votre quotidien en quelques clics');
    }

    public function test_saving_overrides_the_public_site_text(): void
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(SiteContentPage::class)
            ->set('data.home.features_heading', 'Un nouveau titre')
            ->call('save');

        $this->assertSame(
            'Un nouveau titre',
            SiteContent::where('page', 'home')->where('key', 'features_heading')->value('value'),
        );
        $this->assertSame('Un nouveau titre', site_content('home', 'features_heading'));

        $this->get('/')->assertSee('Un nouveau titre');
    }

    public function test_clearing_a_field_falls_back_to_the_default(): void
    {
        $admin = User::factory()->create();
        SiteContent::create(['page' => 'home', 'key' => 'features_heading', 'value' => 'Personnalisé']);

        Livewire::actingAs($admin)
            ->test(SiteContentPage::class)
            ->set('data.home.features_heading', '')
            ->call('save');

        $this->assertSame('Votre quotidien en quelques clics', site_content('home', 'features_heading'));
    }
}

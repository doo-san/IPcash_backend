<?php

namespace Tests\Feature;

use App\Filament\Resources\LegalPageResource;
use App\Models\LegalPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

// Pages légales (Conditions d'utilisation, Confidentialité...) — voir
// App\Models\LegalPage, App\Filament\Resources\LegalPageResource,
// legal_pages() dans app/Support/helpers.php.
class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_two_seeded_pages_are_publicly_reachable(): void
    {
        $this->get('/legal/conditions-utilisation')->assertOk()->assertSee("Conditions d'utilisation");
        $this->get('/legal/confidentialite')->assertOk()->assertSee('Confidentialité');
    }

    public function test_an_unknown_slug_404s(): void
    {
        $this->get('/legal/does-not-exist')->assertNotFound();
    }

    public function test_the_footer_lists_every_legal_page(): void
    {
        $response = $this->get('/');

        $response->assertSee(route('legal.show', 'conditions-utilisation'), false);
        $response->assertSee(route('legal.show', 'confidentialite'), false);
    }

    public function test_admin_can_create_a_new_legal_page_and_it_appears_in_the_footer(): void
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(LegalPageResource\Pages\ManageLegalPages::class)
            ->callAction('create', data: [
                'title' => 'Mentions légales',
                'slug' => 'mentions-legales',
                'content' => '<p>Contenu.</p>',
            ]);

        $this->assertDatabaseHas('legal_pages', ['slug' => 'mentions-legales']);
        $this->get('/legal/mentions-legales')->assertOk()->assertSee('Mentions légales');
        $this->get('/')->assertSee(route('legal.show', 'mentions-legales'), false);
    }

    public function test_deleting_the_last_legal_page_hides_the_footer_column(): void
    {
        LegalPage::query()->delete();

        $this->get('/')->assertDontSee('Conditions d\'utilisation');
    }
}

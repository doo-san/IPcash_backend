<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Couvre le formulaire de contact public (voir ContactController) et les
// pages du site de présentation (voir routes/web.php, groupe `site.*`).
class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_site_pages_render(): void
    {
        foreach (['/', '/fonctionnalites', '/securite', '/a-propos', '/contact'] as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_submitting_the_contact_form_stores_the_message_and_redirects(): void
    {
        $response = $this->post('/contact', [
            'name' => 'Awa Diallo',
            'email' => 'awa@example.com',
            'subject' => 'Question sur les frais',
            'message' => 'Bonjour, quels sont les frais sur un retrait Wave ?',
        ]);

        $response->assertRedirect(route('site.contact'));
        $response->assertSessionHas('contactSent', true);

        $this->assertDatabaseHas('contact_messages', [
            'name' => 'Awa Diallo',
            'email' => 'awa@example.com',
            'subject' => 'Question sur les frais',
        ]);
    }

    public function test_contact_form_requires_all_fields(): void
    {
        $this->post('/contact', [])
            ->assertSessionHasErrors(['name', 'email', 'subject', 'message']);

        $this->assertSame(0, ContactMessage::count());
    }

    public function test_contact_form_rejects_an_invalid_email(): void
    {
        $this->post('/contact', [
            'name' => 'Awa Diallo',
            'email' => 'pas-un-email',
            'subject' => 'Test',
            'message' => 'Un message.',
        ])->assertSessionHasErrors(['email']);
    }
}

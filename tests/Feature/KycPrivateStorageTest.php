<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// Vérifie le mécanisme de service de fichiers privés du disque `kyc`
// (config/filesystems.php) : route dédiée `storage.kyc`, signature
// temporaire obligatoire — voir Illuminate\Filesystem\ServeFile.
class KycPrivateStorageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // `Storage::fake()`/`persistentFake()` nomment toujours le disque
        // fictif "local" en interne (paramètre `$name` non transmis), donc
        // l'URL signée générée pointerait vers la route `storage.local`
        // (`/storage/...`) au lieu de la vraie route `storage.kyc`
        // (`/kyc-storage/...`) qu'on veut précisément vérifier ici. On
        // utilise donc le vrai disque `kyc`, nettoyé avant/après.
        Storage::disk('kyc')->deleteDirectory('123');
        Storage::disk('kyc')->put('123/front.jpg', 'contenu-du-document');
    }

    protected function tearDown(): void
    {
        Storage::disk('kyc')->deleteDirectory('123');
        parent::tearDown();
    }

    public function test_an_unsigned_request_to_the_kyc_storage_route_is_rejected(): void
    {
        $this->get('/kyc-storage/123/front.jpg')->assertStatus(403);
    }

    public function test_a_validly_signed_temporary_url_serves_the_file(): void
    {
        $url = Storage::disk('kyc')->temporaryUrl('123/front.jpg', now()->addMinutes(5));

        $response = $this->get($url)->assertOk();
        $this->assertStringContainsString('contenu-du-document', $response->streamedContent());
    }

    public function test_an_expired_signed_url_is_rejected(): void
    {
        $url = Storage::disk('kyc')->temporaryUrl('123/front.jpg', now()->subMinute());

        $this->get($url)->assertStatus(403);
    }
}

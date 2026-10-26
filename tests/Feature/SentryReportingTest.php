<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use RuntimeException;
use Sentry\ClientBuilder;
use Sentry\Event;
use Sentry\SentrySdk;
use Sentry\State\HubInterface;
use Sentry\Transport\Result;
use Sentry\Transport\ResultStatus;
use Sentry\Transport\TransportInterface;
use Tests\TestCase;

// Vérifie ce qui partirait réellement chez Sentry : l'erreur est bien
// remontée, mais sans corps de requête (PIN, numéro), ni IP, ni utilisateur.
class SentryReportingTest extends TestCase
{
    /** @var list<Event> */
    private array $sent = [];

    private const TEST_DSN = 'https://public@example.ingest.sentry.io/1';

    protected function setUp(): void
    {
        // Le SDK n'installe son middleware (lecture de la requête HTTP) que
        // si un DSN est présent au démarrage de l'application.
        putenv('SENTRY_LARAVEL_DSN='.self::TEST_DSN);
        $_ENV['SENTRY_LARAVEL_DSN'] = $_SERVER['SENTRY_LARAVEL_DSN'] = self::TEST_DSN;

        parent::setUp();

        $sent = &$this->sent;
        $transport = new class($sent) implements TransportInterface
        {
            public function __construct(private array &$sent) {}

            public function send(Event $event): Result
            {
                $this->sent[] = $event;

                return new Result(ResultStatus::success(), $event);
            }

            public function close(?int $timeout = null): Result
            {
                return new Result(ResultStatus::success());
            }
        };

        config(['sentry.transport' => $transport]);
        $this->app->forgetInstance(ClientBuilder::class);
        $this->app->forgetInstance(HubInterface::class);
        SentrySdk::setCurrentHub($this->app->make(HubInterface::class));

        Route::post('/api/test-boom', fn () => throw new RuntimeException('boom'));
    }

    protected function tearDown(): void
    {
        putenv('SENTRY_LARAVEL_DSN');
        unset($_ENV['SENTRY_LARAVEL_DSN'], $_SERVER['SENTRY_LARAVEL_DSN']);

        parent::tearDown();
    }

    public function test_an_api_error_is_reported_without_body_ip_or_user(): void
    {
        $this->postJson('/api/test-boom', ['pin' => '482913', 'phoneNumber' => '+221771234567'])
            ->assertStatus(500);

        $this->assertCount(1, $this->sent);
        $event = $this->sent[0];
        $this->assertSame('boom', $event->getExceptions()[0]->getValue());

        $request = $event->getRequest();
        $this->assertArrayNotHasKey('data', $request);
        $this->assertStringNotContainsString('482913', json_encode($request));
        $this->assertStringNotContainsString('+221771234567', json_encode($request));
        $this->assertNull($event->getUser()?->getIpAddress());
    }

    public function test_pii_collection_cannot_be_turned_on_from_the_environment(): void
    {
        $this->assertFalse(config('sentry.send_default_pii'));
        $this->assertSame('never', config('sentry.max_request_body_size'));
    }
}

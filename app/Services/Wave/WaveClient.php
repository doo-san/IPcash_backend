<?php

namespace App\Services\Wave;

use App\Models\MobileMoneyProvider;
use Illuminate\Support\Facades\Http;

// Client pour l'API Checkout de Wave (dépôt mobile money) — voir
// docs.wave.com/checkout. Contrairement à OM Pay (OrangeMoneyClient), pas
// d'échange OAuth : la clé API sert directement de bearer token, avec une
// signature `Wave-Signature` sur chaque requête (voir WaveSignature) — la
// clé API de ce marchand a la signature de requête activée, confirmée par
// un test réel (`api_secret` du référentiel = le "signing secret" généré
// avec la clé, pas un mot de passe classique).
class WaveClient
{
    public function __construct(private readonly MobileMoneyProvider $provider) {}

    /**
     * @return array{sessionId: string, launchUrl: string}
     */
    public function createCheckoutSession(
        int $amountXof,
        string $reference,
        string $successUrl,
        string $errorUrl,
    ): array {
        $body = json_encode([
            'amount' => (string) $amountXof,
            'currency' => 'XOF',
            'client_reference' => $reference,
            'success_url' => $successUrl,
            'error_url' => $errorUrl,
        ], JSON_THROW_ON_ERROR);

        $response = Http::baseUrl($this->baseUrl())
            ->withToken((string) $this->provider->api_key)
            ->withHeaders(['Wave-Signature' => WaveSignature::header((string) $this->provider->api_secret, $body)])
            ->withBody($body, 'application/json')
            ->post('/v1/checkout/sessions');

        if ($response->failed()) {
            throw new WaveException(
                'Wave Checkout a échoué ('.$response->status().'): '.$this->extractErrorDetail($response->json()),
            );
        }

        $sessionId = $response->json('id');
        $launchUrl = $response->json('wave_launch_url');
        if (! is_string($sessionId) || ! is_string($launchUrl) || $launchUrl === '') {
            throw new WaveException('Wave Checkout : réponse sans wave_launch_url exploitable.');
        }

        return ['sessionId' => $sessionId, 'launchUrl' => $launchUrl];
    }

    private function baseUrl(): string
    {
        return rtrim((string) ($this->provider->api_base_url ?: 'https://api.wave.com'), '/');
    }

    /**
     * @param  array<string, mixed>|null  $body
     */
    private function extractErrorDetail(?array $body): string
    {
        return $body['message'] ?? $body['code'] ?? 'erreur inconnue';
    }
}

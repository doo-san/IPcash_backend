<?php

namespace App\Services\Wave;

use App\Models\MobileMoneyProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

// Client pour les API Checkout (dépôt) et Payout (retrait) de Wave — voir
// docs.wave.com/checkout et docs.wave.com/payout. Contrairement à OM Pay
// (OrangeMoneyClient), pas
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

    // POST /v1/payout — envoie réellement l'argent (pas de "dry run" côté
    // Wave, confirmé sur docs.wave.com/payout). `status` peut valoir
    // `succeeded`, `failed`, ou `processing` (pas de webhook payout
    // documenté pour ce dernier cas — voir la commande planifiée
    // `wave:finalize-payouts` qui interroge `getPayoutStatus` en relais).
    //
    // @return array{id: string, status: string}
    public function sendPayout(int $amountXof, string $mobile): array
    {
        $body = json_encode([
            'currency' => 'XOF',
            'receive_amount' => (string) $amountXof,
            'mobile' => $mobile,
        ], JSON_THROW_ON_ERROR);

        $response = Http::baseUrl($this->baseUrl())
            ->withToken((string) $this->provider->api_key)
            ->withHeaders([
                'Wave-Signature' => WaveSignature::header((string) $this->provider->api_secret, $body),
                'Idempotency-Key' => (string) Str::uuid(),
            ])
            ->withBody($body, 'application/json')
            ->post('/v1/payout');

        if ($response->failed()) {
            throw new WaveException(
                'Wave Payout a échoué ('.$response->status().'): '.$this->extractErrorDetail($response->json()),
            );
        }

        $id = $response->json('id');
        $status = $response->json('status');
        if (! is_string($id) || ! is_string($status)) {
            throw new WaveException('Wave Payout : réponse sans id/status exploitable.');
        }

        return ['id' => $id, 'status' => $status];
    }

    public function getPayoutStatus(string $payoutId): string
    {
        $response = Http::baseUrl($this->baseUrl())
            ->withToken((string) $this->provider->api_key)
            ->withHeaders(['Wave-Signature' => WaveSignature::header((string) $this->provider->api_secret, '')])
            ->get("/v1/payout/{$payoutId}");

        if ($response->failed()) {
            throw new WaveException(
                'Wave Payout (statut) a échoué ('.$response->status().'): '.$this->extractErrorDetail($response->json()),
            );
        }

        $status = $response->json('status');
        if (! is_string($status)) {
            throw new WaveException('Wave Payout (statut) : réponse sans status exploitable.');
        }

        return $status;
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
        $message = $body['message'] ?? $body['code'] ?? 'erreur inconnue';

        // Sur une erreur de validation (400), Wave détaille le champ fautif
        // dans `details` — sans ça, "Request invalid" seul ne dit rien
        // d'exploitable (voir historique des Transaction `failed`).
        if (isset($body['details']) && is_array($body['details'])) {
            $message .= ' — '.json_encode($body['details']);
        }

        return $message;
    }
}

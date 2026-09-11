<?php

namespace App\Services\OrangeMoney;

use App\Models\MobileMoneyProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

// Client pour l'API "OM Pay" d'Orange/Sonatel (dépôt mobile money) — voir
// le Swagger fourni par l'utilisateur (OM Pay API, POST
// /v1/onlinePayment/prepare). Les identifiants (client_id/secret, code
// marchand, URL de base) viennent du référentiel admin `MobileMoneyProvider`
// plutôt que d'être codés en dur, pour rester configurables sans déploiement
// (voir MobileMoneyProviderResource).
//
// NB : au moment d'écrire ce client, l'appel réel à /v1/onlinePayment/prepare
// renvoyait 404 malgré un token OAuth valide (accès probablement pas encore
// pleinement activé côté Orange, ou en-tête additionnel non documenté) — ce
// client applique fidèlement le contrat documenté ; toute erreur HTTP est
// remontée telle quelle via OrangeMoneyException plutôt que masquée.
class OrangeMoneyClient
{
    public function __construct(private readonly MobileMoneyProvider $provider) {}

    public function preparePayment(
        int $amountXof,
        string $reference,
        string $successUrl,
        string $cancelUrl,
        string $callbackUrl,
    ): string {
        $response = Http::baseUrl($this->baseUrl())
            ->withToken($this->accessToken())
            ->acceptJson()
            ->post('/v1/onlinePayment/prepare', [
                'merchantCode' => (int) $this->provider->merchant_code,
                'sitename' => config('app.name'),
                'amount' => $amountXof,
                'urls' => [
                    'successUrl' => $successUrl,
                    'cancelUrl' => $cancelUrl,
                    'callbackUrl' => $callbackUrl,
                ],
                'reference' => $reference,
            ]);

        if ($response->failed()) {
            throw new OrangeMoneyException(
                'OM Pay prepare a échoué ('.$response->status().'): '.$this->extractErrorDetail($response->json()),
            );
        }

        $paymentUrl = $response->json('paymentUrl');
        if (! is_string($paymentUrl) || $paymentUrl === '') {
            throw new OrangeMoneyException('OM Pay prepare : réponse sans paymentUrl exploitable.');
        }

        return $paymentUrl;
    }

    // Un token par prestataire, mis en cache jusqu'à ~30s avant expiration
    // pour éviter un aller-retour OAuth à chaque paiement.
    private function accessToken(): string
    {
        return Cache::remember(
            "orange_money:access_token:{$this->provider->id}",
            now()->addSeconds(270),
            function (): string {
                $response = Http::baseUrl($this->baseUrl())
                    ->asForm()
                    ->withBasicAuth((string) $this->provider->api_key, (string) $this->provider->api_secret)
                    ->post('/oauth/v1/token', ['grant_type' => 'client_credentials']);

                if ($response->failed()) {
                    throw new OrangeMoneyException(
                        'OM Pay OAuth a échoué ('.$response->status().'): '.$this->extractErrorDetail($response->json()),
                    );
                }

                $token = $response->json('access_token');
                if (! is_string($token) || $token === '') {
                    throw new OrangeMoneyException('OM Pay OAuth : réponse sans access_token exploitable.');
                }

                return $token;
            },
        );
    }

    private function baseUrl(): string
    {
        return rtrim((string) ($this->provider->api_base_url ?: 'https://api.orange-sonatel.com'), '/');
    }

    /**
     * @param  array<string, mixed>|null  $body
     */
    private function extractErrorDetail(?array $body): string
    {
        return $body['detail'] ?? $body['error_description'] ?? $body['error'] ?? 'erreur inconnue';
    }
}

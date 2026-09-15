<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MobileMoneyProvider;
use App\Models\Transaction;
use App\Services\Wave\WaveCheckoutFinalizer;
use App\Services\Wave\WaveSignature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

// Reçoit `checkout.session.completed` / `checkout.session.payment_failed`
// de Wave (voir docs.wave.com/webhook) — payload et signature confirmés
// contre la vraie doc publique (contrairement à OM Pay, dont le format de
// callback restait à deviner). La corrélation avec notre `Transaction` se
// fait par `data.client_reference`, qu'on renseigne nous-mêmes avec
// `Transaction.reference` au moment de créer la session.
class WaveWebhookController extends Controller
{
    public function __construct(private readonly WaveCheckoutFinalizer $finalizer) {}

    public function handle(Request $request): JsonResponse
    {
        $rawBody = $request->getContent();
        $signature = $request->header('Wave-Signature');

        $provider = MobileMoneyProvider::query()->get()->first(fn (MobileMoneyProvider $p) => $p->isWave());
        if (! $provider?->api_secret || ! $signature || ! WaveSignature::verify($signature, $provider->api_secret, $rawBody)) {
            Log::warning('[wave] webhook rejeté : signature absente ou invalide');

            return response()->json(['error' => 'invalid_signature'], 401);
        }

        $payload = json_decode($rawBody, true) ?? [];
        Log::info('[wave] webhook reçu', $payload);

        $type = $payload['type'] ?? null;
        $reference = $payload['data']['client_reference'] ?? null;
        if (! is_string($reference)) {
            return response()->json(['received' => true]);
        }

        $transaction = Transaction::where('reference', $reference)->first();
        if (! $transaction || $transaction->status->value !== 'pending') {
            return response()->json(['received' => true]);
        }

        if ($type === 'checkout.session.completed') {
            $this->finalizer->finalize($transaction, 'succeeded');
        } elseif ($type === 'checkout.session.payment_failed') {
            $reason = $payload['data']['last_payment_error']['message'] ?? null;
            $this->finalizer->finalize($transaction, 'cancelled', $reason);
        }
        // Autre type d'événement : on laisse `pending`, rien à faire.

        return response()->json(['received' => true]);
    }
}

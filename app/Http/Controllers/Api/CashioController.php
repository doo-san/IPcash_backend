<?php

namespace App\Http\Controllers\Api;

use App\Enums\FeeScope;
use App\Enums\TransactionType;
use App\Http\Controllers\Api\Concerns\HandlesIdempotency;
use App\Http\Controllers\Api\Concerns\RecordsPendingIntegration;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cashio\AmountOnlyRequest;
use App\Http\Requests\Cashio\CashinRequest;
use App\Http\Requests\Cashio\CashoutRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Account;
use App\Models\FeeRule;
use App\Models\MobileMoneyProvider;
use App\Models\PromoCode;
use App\Models\Transaction;
use App\Services\OrangeMoney\OrangeMoneyClient;
use App\Services\Wave\WaveClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Throwable;

// Implémente le tag `cashio` de openapi.yaml (dépôt/retrait mobile money).
// Distinction importante (CLAUDE.md règle 9) : le dépôt/retrait *mobile
// money* est une opération purement interne au solde IPCash et peut
// simuler une réussite réaliste ; le dépôt par carte/virement bancaire ne
// le peut pas (aucun prestataire réel connecté) — voir `cashinCard`/
// `cashinBank`, qui renvoient toujours `INTEGRATION_PENDING`.
// Exception : Orange Money et Wave sont réellement branchés
// (`OrangeMoneyClient`, `WaveClient`, voir `cashinViaWebRedirect`) — pour
// ces prestataires précis, le dépôt suit le cycle pending→completed/failed
// normal au lieu d'être simulé. Wave a en plus un retrait réel
// (`cashoutViaWave`, API Payout) ; Orange Money n'a que le dépôt pour
// l'instant (pas de documentation de retrait fournie).
//
// Frais (FeeRule, scopes cashIn/cashOut) : 0 par défaut tant qu'aucune
// règle active n'existe pour le scope (voir FeeRule::computeFor) — même
// branchement que `p2pTransfer` dans TransferController, jusqu'ici le
// seul scope réellement lu malgré les 5 proposés dans l'admin. Sur un
// cash-in, les frais sont déduits du montant crédité (l'opérateur mobile
// money prélève sa part avant que l'argent n'entre sur le solde IPCash) ;
// sur un cash-out, ils s'ajoutent au montant débité (même logique que
// `totalXof` pour un transfert P2P).
//
// Code promo (PromoCode) : réduit les frais calculés ci-dessus — champ
// `promoCode` existant côté app (`CashioSwitchFlowController`) mais
// jusqu'ici jamais transmis nulle part, table admin sans aucun effet
// (aucun endpoint dédié). Un code inconnu/expiré/épuisé renvoie une
// erreur explicite plutôt que d'être silencieusement ignoré.
class CashioController extends Controller
{
    use HandlesIdempotency;
    use RecordsPendingIntegration;

    public function cashin(CashinRequest $request): JsonResponse
    {
        /** @var Account $account */
        $account = $request->user();
        $data = $request->validated();
        $idempotencyKey = $request->header('Idempotency-Key');
        $amountXof = (int) $data['amountXof'];
        $feeXof = FeeRule::computeFor(FeeScope::CashIn, $amountXof);

        $promo = $this->resolvePromoCode($data['promoCode'] ?? null, $feeXof);
        if ($promo instanceof JsonResponse) {
            return $promo;
        }
        [$promoCode, $feeXof] = $promo;
        $creditedXof = max(0, $amountXof - $feeXof);

        $existing = $this->findIdempotentTransaction($account, $idempotencyKey, ['amount_xof' => $creditedXof]);
        if ($existing instanceof JsonResponse) {
            return $existing;
        }
        if ($existing) {
            return response()->json((new TransactionResource($existing))->resolve(), 202);
        }

        // Orange Money et Wave sont les seuls opérateurs mobile money
        // réellement branchés (OrangeMoneyClient/OM Pay, WaveClient) —
        // Mixx by Yas reste sur la simulation interne ci-dessous tant qu'il
        // ne l'est pas aussi (CLAUDE.md règle 9 : purement interne au solde
        // IPCash, peut simuler une réussite réaliste).
        $provider = MobileMoneyProvider::find($data['providerId']);
        if ($provider?->isConfigured()) {
            if ($provider->isOrangeMoney()) {
                return $this->cashinViaWebRedirect(
                    $account, $creditedXof, $feeXof, $promoCode, $idempotencyKey,
                    fn (string $reference) => [
                        'paymentUrl' => (new OrangeMoneyClient($provider))->preparePayment(
                            amountXof: $creditedXof + $feeXof,
                            reference: $reference,
                            successUrl: $this->publicUrl('/orange-money/return?status=success'),
                            cancelUrl: $this->publicUrl('/orange-money/return?status=cancel'),
                            callbackUrl: $this->publicUrl('/api/webhooks/orange-money'),
                        ),
                        'providerReference' => null,
                    ],
                );
            }
            if ($provider->isWave()) {
                return $this->cashinViaWebRedirect(
                    $account, $creditedXof, $feeXof, $promoCode, $idempotencyKey,
                    function (string $reference) use ($provider, $creditedXof, $feeXof) {
                        $session = (new WaveClient($provider))->createCheckoutSession(
                            amountXof: $creditedXof + $feeXof,
                            reference: $reference,
                            // `ref` (pas fourni par Wave lui-même à la redirection) permet
                            // au filet de sécurité de /wave/return de retrouver la
                            // transaction sans dépendre du webhook (voir routes/web.php).
                            successUrl: $this->publicUrl('/wave/return?status=success&ref='.$reference),
                            errorUrl: $this->publicUrl('/wave/return?status=error&ref='.$reference),
                        );

                        return ['paymentUrl' => $session['launchUrl'], 'providerReference' => $session['sessionId']];
                    },
                );
            }
        }

        $transaction = DB::transaction(function () use ($account, $creditedXof, $feeXof, $promoCode, $idempotencyKey) {
            $account->increment('balance_xof', $creditedXof);
            $promoCode?->increment('redemptions_count');

            return $account->transactions()->create([
                'type' => TransactionType::CashIn,
                'status' => 'completed',
                'amount_xof' => $creditedXof,
                'fee_xof' => $feeXof,
                'reference' => 'CASHIN-'.strtoupper(Str::random(10)),
                'idempotency_key' => $idempotencyKey,
            ]);
        });

        return response()->json((new TransactionResource($transaction))->resolve(), 202);
    }

    // Dépôt via un prestataire à redirection web réellement branché (OM
    // Pay, Wave Checkout...) : le solde n'est crédité qu'à la confirmation
    // du webhook correspondant (ou, pour Wave, du filet de sécurité posé
    // sur /wave/return — voir routes/web.php), jamais ici — contrairement
    // au cash-in interne ci-dessus, cette opération dépend d'un vrai tiers
    // (CLAUDE.md règle 3, trois états). `$preparePayment` reçoit la
    // référence de la transaction déjà créée et renvoie
    // `['paymentUrl' => ..., 'providerReference' => ...]` (la référence
    // prestataire — l'id de session Wave, notamment — sert à interroger
    // son statut plus tard ; `null` quand le prestataire n'en fournit pas),
    // ou lève n'importe quelle exception en cas d'échec côté prestataire.
    private function cashinViaWebRedirect(
        Account $account,
        int $creditedXof,
        int $feeXof,
        ?PromoCode $promoCode,
        ?string $idempotencyKey,
        \Closure $preparePayment,
    ): JsonResponse {
        $transaction = $account->transactions()->create([
            'type' => TransactionType::CashIn,
            'status' => 'pending',
            'amount_xof' => $creditedXof,
            'fee_xof' => $feeXof,
            'reference' => 'CASHIN-'.strtoupper(Str::random(10)),
            'idempotency_key' => $idempotencyKey,
        ]);

        try {
            ['paymentUrl' => $paymentUrl, 'providerReference' => $providerReference] = $preparePayment($transaction->reference);
        } catch (Throwable $e) {
            $transaction->update(['status' => 'failed', 'failure_reason' => $e->getMessage()]);

            return response()->json((new TransactionResource($transaction->fresh()))->resolve(), 202);
        }

        if ($providerReference !== null) {
            $transaction->update(['provider_reference' => $providerReference]);
        }

        $promoCode?->increment('redemptions_count');

        $json = (new TransactionResource($transaction))->resolve();
        $json['paymentUrl'] = $paymentUrl;

        return response()->json($json, 202);
    }

    public function cashinCard(AmountOnlyRequest $request): JsonResponse
    {
        return $this->pendingDeposit($request, 'CASHIN-CARD');
    }

    public function cashinBank(AmountOnlyRequest $request): JsonResponse
    {
        return $this->pendingDeposit($request, 'CASHIN-BANK');
    }

    public function cashinPaypal(AmountOnlyRequest $request): JsonResponse
    {
        return $this->pendingDeposit($request, 'CASHIN-PAYPAL');
    }

    // Dépôt carte / virement / PayPal : aucun prestataire branché. On
    // enregistre la tentative (Transaction `failed`, visible dans l'admin)
    // puis on renvoie `INTEGRATION_PENDING`.
    private function pendingDeposit(AmountOnlyRequest $request, string $referencePrefix): JsonResponse
    {
        $this->recordPendingIntegrationAttempt(
            $request->user(),
            TransactionType::CashIn,
            (int) $request->validated('amountXof'),
            $request->header('Idempotency-Key'),
            $referencePrefix,
        );

        return $this->integrationPending();
    }

    // Construit une URL absolue à partir d'`APP_URL` plutôt que via
    // `route()`/`url()` : sans proxy de confiance configuré (`TrustProxies`),
    // Laravel dérive le domaine racine de la requête entrante plutôt que
    // d'`APP_URL` — en dev, un appel direct sur l'IP locale ou même via le
    // tunnel Cloudflare (qui relaie en HTTP simple en interne) produisait
    // une URL locale que Wave/Orange rejettent (ils exigent du HTTPS
    // public). Ces URLs doivent toujours pointer vers l'adresse publique
    // configurée, jamais vers l'hôte de la requête qui nous a appelés.
    private function publicUrl(string $path): string
    {
        return rtrim((string) config('app.url'), '/').$path;
    }

    public function cashout(CashoutRequest $request): JsonResponse
    {
        /** @var Account $account */
        $account = $request->user();
        $data = $request->validated();
        $idempotencyKey = $request->header('Idempotency-Key');
        $amountXof = (int) $data['amountXof'];
        $feeXof = FeeRule::computeFor(FeeScope::CashOut, $amountXof);

        if ($account->pin_hash === null || ! Hash::check($data['pin'], $account->pin_hash)) {
            return response()->json(['code' => 'UNAUTHORIZED', 'message' => 'Code secret invalide.'], 401);
        }

        $promo = $this->resolvePromoCode($data['promoCode'] ?? null, $feeXof);
        if ($promo instanceof JsonResponse) {
            return $promo;
        }
        [$promoCode, $feeXof] = $promo;
        $totalXof = $amountXof + $feeXof;

        $existing = $this->findIdempotentTransaction($account, $idempotencyKey, ['amount_xof' => -$totalXof]);
        if ($existing instanceof JsonResponse) {
            return $existing;
        }
        if ($existing) {
            return response()->json((new TransactionResource($existing))->resolve(), 202);
        }

        if ($account->balance_xof < $totalXof) {
            return response()->json(['code' => 'INSUFFICIENT_FUNDS', 'message' => 'Solde insuffisant pour effectuer cette opération.'], 402);
        }

        // Wave est le seul opérateur mobile money avec un retrait réellement
        // branché (WaveClient::sendPayout) — Orange Money (dépôt seulement
        // pour l'instant) et Mixx by Yas restent sur la simulation interne.
        $provider = MobileMoneyProvider::find($data['providerId']);
        if ($provider?->isWave() && $provider->isConfigured()) {
            return $this->cashoutViaWave($account, $provider, $amountXof, $feeXof, $totalXof, $promoCode, $idempotencyKey);
        }

        $transaction = DB::transaction(function () use ($account, $totalXof, $feeXof, $promoCode, $idempotencyKey) {
            $account->decrement('balance_xof', $totalXof);
            $promoCode?->increment('redemptions_count');

            return $account->transactions()->create([
                'type' => TransactionType::CashOut,
                'status' => 'completed',
                'amount_xof' => -$totalXof,
                'fee_xof' => $feeXof,
                'reference' => 'CASHOUT-'.strtoupper(Str::random(10)),
                'idempotency_key' => $idempotencyKey,
            ]);
        });

        return response()->json((new TransactionResource($transaction))->resolve(), 202);
    }

    // Retrait Wave réel : contrairement au dépôt (Checkout, redirection
    // web), `POST /v1/payout` déplace l'argent immédiatement — pas d'étape
    // de confirmation côté client, donc pas de `paymentUrl` ici. Le solde
    // n'est débité qu'après avoir la confirmation `succeeded` de Wave
    // (jamais avant, règle 3) ; `processing` (pas de webhook payout
    // documenté) laisse la transaction `pending`, avec l'id Wave dans
    // `provider_reference` pour que `wave:finalize-payouts` puisse la
    // relancer plus tard.
    private function cashoutViaWave(
        Account $account,
        MobileMoneyProvider $provider,
        int $amountXof,
        int $feeXof,
        int $totalXof,
        ?PromoCode $promoCode,
        ?string $idempotencyKey,
    ): JsonResponse {
        $transaction = $account->transactions()->create([
            'type' => TransactionType::CashOut,
            'status' => 'pending',
            'amount_xof' => -$totalXof,
            'fee_xof' => $feeXof,
            'reference' => 'CASHOUT-'.strtoupper(Str::random(10)),
            'idempotency_key' => $idempotencyKey,
        ]);

        try {
            $result = (new WaveClient($provider))->sendPayout(
                amountXof: $amountXof,
                mobile: $account->phone_number,
            );
        } catch (Throwable $e) {
            $transaction->update(['status' => 'failed', 'failure_reason' => $e->getMessage()]);

            return response()->json((new TransactionResource($transaction->fresh()))->resolve(), 202);
        }

        $this->applyWavePayoutStatus($transaction, $account, $totalXof, $promoCode, $result['status'], $result['id']);

        return response()->json((new TransactionResource($transaction->fresh()))->resolve(), 202);
    }

    // Partagé avec `FinalizeWavePayouts` (relance sur un payout resté
    // `processing`) pour ne pas dupliquer la logique de transition d'état.
    public function applyWavePayoutStatus(
        Transaction $transaction,
        Account $account,
        int $totalXof,
        ?PromoCode $promoCode,
        string $waveStatus,
        string $payoutId,
    ): void {
        if ($waveStatus === 'succeeded') {
            DB::transaction(function () use ($account, $totalXof, $promoCode, $transaction) {
                $account->decrement('balance_xof', $totalXof);
                $promoCode?->increment('redemptions_count');
                $transaction->update(['status' => 'completed', 'provider_reference' => null]);
            });

            return;
        }

        if ($waveStatus === 'failed' || $waveStatus === 'reversed') {
            $transaction->update([
                'status' => 'failed',
                'failure_reason' => 'Le versement Wave a échoué.',
                'provider_reference' => null,
            ]);

            return;
        }

        // `processing` : on ne débite rien tant que ce n'est pas confirmé.
        $transaction->update(['provider_reference' => $payoutId]);
    }

    /**
     * Valide un code promo optionnel et applique sa remise aux frais déjà
     * calculés. `null` en entrée → aucun changement (chemin normal, code
     * promo non renseigné). Un code renseigné mais invalide/expiré/épuisé
     * renvoie une erreur explicite plutôt que d'être ignoré en silence.
     *
     * @return array{0: PromoCode|null, 1: int}|JsonResponse
     */
    private function resolvePromoCode(?string $code, int $feeXof): array|JsonResponse
    {
        if ($code === null || $code === '') {
            return [null, $feeXof];
        }

        $promo = PromoCode::find(Str::upper($code));
        if (! $promo || ! $promo->isValidNow()) {
            return response()->json(['code' => 'PROMO_CODE_INVALID', 'message' => 'Ce code promo est invalide ou n\'est plus utilisable.'], 400);
        }

        $discount = $promo->discount_type === 'percent'
            ? intdiv($feeXof * $promo->discount_value, 10000)
            : $promo->discount_value;

        return [$promo, max(0, $feeXof - $discount)];
    }

    private function integrationPending(): JsonResponse
    {
        return response()->json([
            'code' => 'INTEGRATION_PENDING',
            'message' => 'Aucun prestataire réel n\'est encore connecté pour cette opération.',
        ], 400);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Enums\CardStatus;
use App\Enums\FeeScope;
use App\Enums\TransactionType;
use App\Http\Controllers\Api\Concerns\HandlesIdempotency;
use App\Http\Controllers\Controller;
use App\Http\Requests\Card\GenerateEphemeralCardRequest;
use App\Http\Requests\Card\RevealRequest;
use App\Http\Requests\Cashio\AmountOnlyRequest;
use App\Http\Resources\CardResource;
use App\Http\Resources\EphemeralCardResource;
use App\Http\Resources\TransactionResource;
use App\Models\Account;
use App\Models\EphemeralCard;
use App\Models\FeeRule;
use App\Support\Luhn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

// Implémente le tag `cards` de openapi.yaml.
class CardController extends Controller
{
    use HandlesIdempotency;

    public function index(Request $request): AnonymousResourceCollection
    {
        return CardResource::collection($request->user()->cards);
    }

    public function freeze(Request $request, string $id): CardResource|Response
    {
        return $this->setStatus($request, $id, CardStatus::Frozen);
    }

    public function unfreeze(Request $request, string $id): CardResource|Response
    {
        return $this->setStatus($request, $id, CardStatus::Active);
    }

    public function destroy(Request $request, string $id): JsonResponse|Response
    {
        /** @var Account $account */
        $account = $request->user();
        $idempotencyKey = $request->header('Idempotency-Key');

        // Rien à comparer (aucun paramètre client, le montant reversé est
        // déterminé côté serveur) : un rejeu de la même clé renvoie
        // simplement 204 sans revérifier la carte, qui n'existe déjà plus.
        $existing = $this->findIdempotentTransaction($account, $idempotencyKey, []);
        if ($existing instanceof JsonResponse) {
            return $existing;
        }
        if ($existing) {
            return response()->noContent();
        }

        $card = $account->cards()->find($id);
        if (! $card) {
            return $this->notFound();
        }

        // Suppression de carte : opération purement interne au solde
        // IPCash (règle 9 de CLAUDE.md) — le solde restant est toujours
        // reversé sur le compte principal avant suppression, jamais perdu.
        DB::transaction(function () use ($account, $card, $idempotencyKey) {
            $sweptAmount = $card->balance_xof;

            if ($sweptAmount > 0) {
                $account->increment('balance_xof', $sweptAmount);
                $account->transactions()->create([
                    'type' => TransactionType::CardWithdrawal,
                    'status' => 'completed',
                    'amount_xof' => $sweptAmount,
                    'reference' => 'CARDDEL-'.strtoupper(Str::random(10)),
                    'idempotency_key' => $idempotencyKey,
                ]);
            }

            $card->delete();
        });

        return response()->noContent();
    }

    public function recharge(AmountOnlyRequest $request, string $id): JsonResponse|Response
    {
        /** @var Account $account */
        $account = $request->user();
        $card = $account->cards()->find($id);
        if (! $card) {
            return $this->notFound();
        }

        $amountXof = (int) $request->validated('amountXof');
        $idempotencyKey = $request->header('Idempotency-Key');
        // FeeRule, scope cardTopUp — 0 par défaut tant qu'aucune règle
        // active n'existe (voir FeeRule::computeFor). Le solde principal
        // est débité du montant + frais, la carte n'est créditée que du
        // montant demandé — même logique que `totalXof` pour un transfert
        // P2P ou un cash-out.
        $feeXof = FeeRule::computeFor(FeeScope::CardTopUp, $amountXof);
        $totalXof = $amountXof + $feeXof;

        $existing = $this->findIdempotentTransaction($account, $idempotencyKey, ['card_id' => $card->id, 'amount_xof' => $totalXof]);
        if ($existing instanceof JsonResponse) {
            return $existing;
        }
        if ($existing) {
            return response()->json((new TransactionResource($existing))->resolve(), 202);
        }

        if ($account->balance_xof < $totalXof) {
            return response()->json(['code' => 'INSUFFICIENT_FUNDS', 'message' => 'Solde principal insuffisant.'], 402);
        }

        $transaction = DB::transaction(function () use ($account, $card, $amountXof, $totalXof, $feeXof, $idempotencyKey) {
            $account->decrement('balance_xof', $totalXof);
            $card->increment('balance_xof', $amountXof);

            return $account->transactions()->create([
                'card_id' => $card->id,
                'type' => TransactionType::CardTopUp,
                'status' => 'completed',
                'amount_xof' => $totalXof,
                'fee_xof' => $feeXof,
                'reference' => 'CARDTOPUP-'.strtoupper(Str::random(10)),
                'idempotency_key' => $idempotencyKey,
                'main_balance_after_xof' => $account->balance_xof,
                'card_balance_after_xof' => $card->balance_xof,
                'card_last4' => $card->last4,
            ]);
        });

        return response()->json((new TransactionResource($transaction))->resolve(), 202);
    }

    public function transferOut(AmountOnlyRequest $request, string $id): JsonResponse|Response
    {
        /** @var Account $account */
        $account = $request->user();
        $card = $account->cards()->find($id);
        if (! $card) {
            return $this->notFound();
        }

        $amountXof = (int) $request->validated('amountXof');
        $idempotencyKey = $request->header('Idempotency-Key');

        $existing = $this->findIdempotentTransaction($account, $idempotencyKey, ['card_id' => $card->id, 'amount_xof' => -$amountXof]);
        if ($existing instanceof JsonResponse) {
            return $existing;
        }
        if ($existing) {
            return response()->json((new TransactionResource($existing))->resolve(), 202);
        }

        if ($card->balance_xof < $amountXof) {
            return response()->json(['code' => 'INSUFFICIENT_FUNDS', 'message' => 'Solde de la carte insuffisant.'], 402);
        }

        $transaction = DB::transaction(function () use ($account, $card, $amountXof, $idempotencyKey) {
            $card->decrement('balance_xof', $amountXof);
            $account->increment('balance_xof', $amountXof);

            return $account->transactions()->create([
                'card_id' => $card->id,
                'type' => TransactionType::CardWithdrawal,
                'status' => 'completed',
                'amount_xof' => -$amountXof,
                'reference' => 'CARDOUT-'.strtoupper(Str::random(10)),
                'idempotency_key' => $idempotencyKey,
                'main_balance_after_xof' => $account->balance_xof,
                'card_balance_after_xof' => $card->balance_xof,
                'card_last4' => $card->last4,
            ]);
        });

        return response()->json((new TransactionResource($transaction))->resolve(), 202);
    }

    public function transactions(Request $request, string $id): AnonymousResourceCollection|Response
    {
        /** @var Account $account */
        $account = $request->user();
        $card = $account->cards()->find($id);
        if (! $card) {
            return $this->notFound();
        }

        return TransactionResource::collection($card->transactions()->latest()->get());
    }

    public function reveal(RevealRequest $request, string $id): JsonResponse|Response
    {
        /** @var Account $account */
        $account = $request->user();
        $card = $account->cards()->find($id);
        if (! $card) {
            return $this->notFound();
        }

        if ($account->pin_hash === null || ! Hash::check($request->validated('pin'), $account->pin_hash)) {
            return response()->json(['code' => 'UNAUTHORIZED', 'message' => 'Code secret invalide.'], 401);
        }

        $rawToken = Str::random(64);
        $expiresInSeconds = 30;
        $card->revealTokens()->create([
            'token_hash' => Hash::make($rawToken),
            'expires_at' => now()->addSeconds($expiresInSeconds),
        ]);

        return response()->json([
            'revealToken' => $rawToken,
            'expiresInSeconds' => $expiresInSeconds,
        ]);
    }

    public function generateEphemeralCard(GenerateEphemeralCardRequest $request, string $id): JsonResponse|Response
    {
        /** @var Account $account */
        $account = $request->user();
        $card = $account->cards()->find($id);
        if (! $card) {
            return $this->notFound();
        }

        if ($account->pin_hash === null || ! Hash::check($request->validated('pin'), $account->pin_hash)) {
            return response()->json(['code' => 'UNAUTHORIZED', 'message' => 'Code secret invalide.'], 401);
        }

        if ($account->ephemeralCard) {
            return response()->json((new EphemeralCardResource($account->ephemeralCard))->resolve(), 202);
        }

        $ephemeralCard = $this->issueEphemeralCard($account);

        return response()->json((new EphemeralCardResource($ephemeralCard))->resolve(), 202);
    }

    public function getEphemeralCard(Request $request): JsonResponse|Response
    {
        $ephemeralCard = $request->user()->ephemeralCard;

        if (! $ephemeralCard) {
            // `response()->json(null)` encode en fait `{}` et même
            // `new JsonResponse('null', ..., json: true)` réencode la
            // chaîne (donne `"null"`, entre guillemets) — Laravel ne sait
            // renvoyer un vrai `null` JSON qu'en construisant la réponse
            // à la main. Le contrat exige explicitement `null` ici
            // (`oneOf: [EphemeralCard, "null"]`).
            return response('null', 200)->header('Content-Type', 'application/json');
        }

        return response()->json((new EphemeralCardResource($ephemeralCard))->resolve());
    }

    public function destroyEphemeralCard(Request $request): Response
    {
        /** @var Account $account */
        $account = $request->user();
        $ephemeralCard = $account->ephemeralCard;
        if (! $ephemeralCard) {
            return $this->notFound();
        }

        DB::transaction(function () use ($account, $ephemeralCard) {
            if ($ephemeralCard->balance_xof > 0) {
                $account->increment('balance_xof', $ephemeralCard->balance_xof);
            }
            $ephemeralCard->destroyed_at = now();
            $ephemeralCard->save();
        });

        return response()->noContent();
    }

    public function rechargeEphemeralCard(AmountOnlyRequest $request): JsonResponse|Response
    {
        /** @var Account $account */
        $account = $request->user();
        $ephemeralCard = $account->ephemeralCard;
        if (! $ephemeralCard) {
            return $this->notFound();
        }
        $amountXof = (int) $request->validated('amountXof');
        $idempotencyKey = $request->header('Idempotency-Key');

        $existing = $this->findIdempotentTransaction($account, $idempotencyKey, ['amount_xof' => -$amountXof]);
        if ($existing instanceof JsonResponse) {
            return $existing;
        }
        if ($existing) {
            return response()->json((new TransactionResource($existing))->resolve(), 202);
        }

        if ($account->balance_xof < $amountXof) {
            return response()->json(['code' => 'INSUFFICIENT_FUNDS', 'message' => 'Solde insuffisant pour effectuer cette opération.'], 402);
        }

        $transaction = DB::transaction(function () use ($account, $ephemeralCard, $amountXof, $idempotencyKey) {
            $account->decrement('balance_xof', $amountXof);
            $ephemeralCard->increment('balance_xof', $amountXof);

            return $account->transactions()->create([
                'type' => TransactionType::CardTopUp,
                'status' => 'completed',
                'amount_xof' => -$amountXof,
                'reference' => 'CARDTOPUP-'.strtoupper(Str::random(10)),
                'idempotency_key' => $idempotencyKey,
            ]);
        });

        return response()->json((new TransactionResource($transaction))->resolve(), 202);
    }

    public function transferOutEphemeralCard(AmountOnlyRequest $request): JsonResponse|Response
    {
        /** @var Account $account */
        $account = $request->user();
        $ephemeralCard = $account->ephemeralCard;
        if (! $ephemeralCard) {
            return $this->notFound();
        }
        $amountXof = (int) $request->validated('amountXof');
        $idempotencyKey = $request->header('Idempotency-Key');

        $existing = $this->findIdempotentTransaction($account, $idempotencyKey, ['amount_xof' => $amountXof]);
        if ($existing instanceof JsonResponse) {
            return $existing;
        }
        if ($existing) {
            return response()->json((new TransactionResource($existing))->resolve(), 202);
        }

        if ($ephemeralCard->balance_xof < $amountXof) {
            return response()->json(['code' => 'INSUFFICIENT_FUNDS', 'message' => 'Solde de la carte éphémère insuffisant.'], 402);
        }

        $transaction = DB::transaction(function () use ($account, $ephemeralCard, $amountXof, $idempotencyKey) {
            $ephemeralCard->decrement('balance_xof', $amountXof);
            $account->increment('balance_xof', $amountXof);

            return $account->transactions()->create([
                'type' => TransactionType::CardWithdrawal,
                'status' => 'completed',
                'amount_xof' => $amountXof,
                'reference' => 'CARDOUT-'.strtoupper(Str::random(10)),
                'idempotency_key' => $idempotencyKey,
            ]);
        });

        return response()->json((new TransactionResource($transaction))->resolve(), 202);
    }

    private function setStatus(Request $request, string $id, CardStatus $status): CardResource|Response
    {
        /** @var Account $account */
        $account = $request->user();
        $card = $account->cards()->find($id);
        if (! $card) {
            return $this->notFound();
        }

        // Un blocage administratif (`CardStatus::Blocked`) ne se lève que
        // par le staff (CardResource / EditCard) — le client ne peut ni le
        // poser ni le retirer via geler/dégeler.
        if ($card->status === CardStatus::Blocked) {
            return response()->json([
                'code' => 'CARD_BLOCKED',
                'message' => 'Cette carte a été bloquée. Contactez le support.',
            ], 403);
        }

        $card->status = $status;
        $card->save();

        return new CardResource($card);
    }

    private function notFound(): Response
    {
        return response()->json(['code' => 'NOT_FOUND', 'message' => 'Carte introuvable.'], 404);
    }

    // Exception explicite et isolée à la règle 9 de CLAUDE.md, à la
    // demande du produit — voir `EphemeralCard` : numéro/CVV générés
    // localement, volontairement invalides au sens de Luhn pour qu'un
    // formulaire de paiement réel les rejette plutôt que de laisser croire
    // à une tentative de paiement en cours. Jamais journalisés (le modèle
    // les chiffre au repos malgré tout, voir `EphemeralCard::casts()`).
    private function issueEphemeralCard(Account $account): EphemeralCard
    {
        $seed = str_replace('-', '', (string) Str::uuid());
        $first15 = [];
        for ($i = 0; $i < 15; $i++) {
            $first15[] = hexdec($seed[$i]) % 10;
        }
        $validCheckDigit = Luhn::checkDigit($first15);
        $invalidCheckDigit = ($validCheckDigit + 1) % 10;
        $number = implode('', $first15).$invalidCheckDigit;
        $cvv = (string) ((hexdec(substr($seed, 15, 3)) % 900) + 100);

        $expiry = now()->addMonthNoOverflow()->startOfMonth();

        return $account->ephemeralCard()->create([
            'number' => $number,
            'cvv' => $cvv,
            'expiry_month' => $expiry->month,
            'expiry_year' => $expiry->year,
            'balance_xof' => 0,
        ]);
    }
}

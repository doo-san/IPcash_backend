<?php

namespace App\Http\Controllers\Api;

use App\Enums\FeeScope;
use App\Enums\TransactionType;
use App\Http\Controllers\Api\Concerns\HandlesIdempotency;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cashio\ConvertFromXofRequest;
use App\Http\Requests\Cashio\ConvertToXofRequest;
use App\Http\Requests\Cashio\PayMerchantRequest;
use App\Http\Requests\Cashio\SendRequest;
use App\Http\Resources\ForeignBalanceResource;
use App\Http\Resources\TransactionResource;
use App\Models\Account;
use App\Models\ExchangeRate;
use App\Models\FeeRule;
use App\Models\ForeignBalance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

// Implémente le tag `cashio` de openapi.yaml pour les sous-comptes devise
// IPchange — exception isolée à la règle 1 de CLAUDE.md (solde réel en
// devise étrangère, voir `ForeignBalance`). Convertir et envoyer entre
// utilisateurs IPCash restent purement internes (l'argent ne quitte
// jamais IPCash) et peuvent simuler une réussite ; payer un marchand ne
// le peut pas (aucun prestataire réel connecté, règle 9).
class ForeignBalanceController extends Controller
{
    use HandlesIdempotency;

    public function index(Request $request): AnonymousResourceCollection
    {
        return ForeignBalanceResource::collection($request->user()->foreignBalances);
    }

    public function convertFromXof(ConvertFromXofRequest $request): JsonResponse
    {
        /** @var Account $account */
        $account = $request->user();
        $data = $request->validated();
        $idempotencyKey = $request->header('Idempotency-Key');
        $amountXof = (int) $data['amountXof'];
        $currencyCode = strtoupper($data['currencyCode']);
        // FeeRule, scope foreignExchange (IPchange) — 0 par défaut tant
        // qu'aucune règle active n'existe. Le montant converti reste
        // `amountXof` ; les frais s'ajoutent au débit du solde principal.
        $feeXof = FeeRule::computeFor(FeeScope::ForeignExchange, $amountXof);
        $totalXof = $amountXof + $feeXof;

        $rate = ExchangeRate::find($currencyCode);
        if (! $rate) {
            return response()->json(['code' => 'BAD_REQUEST', 'message' => 'Devise inconnue.'], 400);
        }

        $existing = $this->findIdempotentTransaction($account, $idempotencyKey, ['amount_xof' => -$totalXof, 'foreign_currency_code' => $currencyCode]);
        if ($existing instanceof JsonResponse) {
            return $existing;
        }
        if ($existing) {
            return response()->json((new TransactionResource($existing))->resolve(), 202);
        }

        if ($account->balance_xof < $totalXof) {
            return response()->json(['code' => 'INSUFFICIENT_FUNDS', 'message' => 'Solde insuffisant pour effectuer cette opération.'], 402);
        }

        $minorUnits = (int) round(($amountXof / (float) $rate->rate_to_xof) * 100);

        $transaction = DB::transaction(function () use ($account, $totalXof, $feeXof, $currencyCode, $minorUnits, $idempotencyKey) {
            $account->decrement('balance_xof', $totalXof);
            $balance = $account->foreignBalances()->firstOrCreate(['currency_code' => $currencyCode]);
            $balance->increment('amount_minor_units', $minorUnits);

            return $account->transactions()->create([
                'type' => TransactionType::ForeignExchangeOut,
                'status' => 'completed',
                'amount_xof' => -$totalXof,
                'fee_xof' => $feeXof,
                'reference' => 'FX-'.strtoupper(Str::random(10)),
                'foreign_currency_code' => $currencyCode,
                'foreign_balance_after_minor_units' => $balance->fresh()->amount_minor_units,
                'idempotency_key' => $idempotencyKey,
            ]);
        });

        return response()->json((new TransactionResource($transaction))->resolve(), 202);
    }

    public function convertToXof(ConvertToXofRequest $request): JsonResponse
    {
        /** @var Account $account */
        $account = $request->user();
        $data = $request->validated();
        $idempotencyKey = $request->header('Idempotency-Key');
        $minorUnits = (int) $data['amountMinorUnits'];
        $currencyCode = strtoupper($data['currencyCode']);

        $rate = ExchangeRate::find($currencyCode);
        if (! $rate) {
            return response()->json(['code' => 'BAD_REQUEST', 'message' => 'Devise inconnue.'], 400);
        }

        $balance = $account->foreignBalances()->where('currency_code', $currencyCode)->first();
        if (! $balance || $balance->amount_minor_units < $minorUnits) {
            return response()->json(['code' => 'INSUFFICIENT_FUNDS', 'message' => 'Solde du sous-compte insuffisant.'], 402);
        }

        $existing = $this->findIdempotentTransaction($account, $idempotencyKey, ['foreign_currency_code' => $currencyCode]);
        if ($existing instanceof JsonResponse) {
            return $existing;
        }
        if ($existing) {
            return response()->json((new TransactionResource($existing))->resolve(), 202);
        }

        $amountXof = (int) round(($minorUnits / 100) * (float) $rate->rate_to_xof);
        // Sens inverse : les frais sont retenus sur le montant XOF crédité.
        $feeXof = FeeRule::computeFor(FeeScope::ForeignExchange, $amountXof);
        $creditedXof = max(0, $amountXof - $feeXof);

        $transaction = DB::transaction(function () use ($account, $creditedXof, $feeXof, $currencyCode, $minorUnits, $balance, $idempotencyKey) {
            $balance->decrement('amount_minor_units', $minorUnits);
            $account->increment('balance_xof', $creditedXof);

            return $account->transactions()->create([
                'type' => TransactionType::ForeignExchangeIn,
                'status' => 'completed',
                'amount_xof' => $creditedXof,
                'fee_xof' => $feeXof,
                'reference' => 'FX-'.strtoupper(Str::random(10)),
                'foreign_currency_code' => $currencyCode,
                'foreign_balance_after_minor_units' => $balance->fresh()->amount_minor_units,
                'idempotency_key' => $idempotencyKey,
            ]);
        });

        return response()->json((new TransactionResource($transaction))->resolve(), 202);
    }

    public function payMerchant(PayMerchantRequest $request): JsonResponse
    {
        return response()->json([
            'code' => 'INTEGRATION_PENDING',
            'message' => 'Aucun prestataire de paiement marchand n\'est encore connecté.',
        ], 400);
    }

    public function send(SendRequest $request): JsonResponse
    {
        /** @var Account $account */
        $account = $request->user();
        $data = $request->validated();
        $idempotencyKey = $request->header('Idempotency-Key');
        $minorUnits = (int) $data['amountMinorUnits'];
        $currencyCode = strtoupper($data['currencyCode']);

        if ($account->pin_hash === null || ! Hash::check($data['pin'], $account->pin_hash)) {
            return response()->json(['code' => 'UNAUTHORIZED', 'message' => 'Code secret invalide.'], 401);
        }

        $recipient = Account::where('phone_number', $data['recipientPhoneNumber'])->first();
        if (! $recipient || $recipient->id === $account->id) {
            return response()->json(['code' => 'RECIPIENT_NOT_FOUND', 'message' => 'Destinataire invalide.'], 400);
        }

        $balance = $account->foreignBalances()->where('currency_code', $currencyCode)->first();
        if (! $balance || $balance->amount_minor_units < $minorUnits) {
            return response()->json(['code' => 'INSUFFICIENT_FUNDS', 'message' => 'Solde du sous-compte insuffisant.'], 402);
        }

        $existing = $this->findIdempotentTransaction($account, $idempotencyKey, ['foreign_currency_code' => $currencyCode]);
        if ($existing instanceof JsonResponse) {
            return $existing;
        }
        if ($existing) {
            return response()->json((new TransactionResource($existing))->resolve(), 202);
        }

        $transaction = DB::transaction(function () use ($account, $recipient, $currencyCode, $minorUnits, $balance, $idempotencyKey) {
            $balance->decrement('amount_minor_units', $minorUnits);
            $recipientBalance = $recipient->foreignBalances()->firstOrCreate(['currency_code' => $currencyCode]);
            $recipientBalance->increment('amount_minor_units', $minorUnits);

            $reference = 'FXSEND-'.strtoupper(Str::random(10));

            $recipient->transactions()->create([
                'type' => TransactionType::ForeignExchangeIn,
                'status' => 'completed',
                'amount_xof' => 0,
                'counterparty_name' => $account->fullName() ?? $account->phone_number,
                'counterparty_phone_number' => $account->phone_number,
                'reference' => "{$reference}-R",
                'foreign_currency_code' => $currencyCode,
                'foreign_balance_after_minor_units' => $recipientBalance->fresh()->amount_minor_units,
            ]);

            return $account->transactions()->create([
                'type' => TransactionType::ForeignTransferOut,
                'status' => 'completed',
                'amount_xof' => 0,
                'counterparty_name' => $recipient->fullName() ?? $recipient->phone_number,
                'counterparty_phone_number' => $recipient->phone_number,
                'reference' => $reference,
                'foreign_currency_code' => $currencyCode,
                'foreign_balance_after_minor_units' => $balance->fresh()->amount_minor_units,
                'idempotency_key' => $idempotencyKey,
            ]);
        });

        return response()->json((new TransactionResource($transaction))->resolve(), 202);
    }
}

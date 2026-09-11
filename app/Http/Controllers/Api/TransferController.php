<?php

namespace App\Http\Controllers\Api;

use App\Enums\FeeScope;
use App\Enums\KycVerificationStatus;
use App\Enums\TransactionType;
use App\Http\Controllers\Api\Concerns\HandlesIdempotency;
use App\Http\Controllers\Controller;
use App\Http\Requests\Transfer\P2pTransferRequest;
use App\Http\Requests\Transfer\QuoteRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Account;
use App\Models\FeeRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

// Implémente le tag `transfers` de openapi.yaml.
class TransferController extends Controller
{
    use HandlesIdempotency;

    public function quote(QuoteRequest $request): JsonResponse
    {
        /** @var Account $account */
        $account = $request->user();
        $data = $request->validated();

        $recipient = $this->findRecipient($account, $data['recipientPhoneNumber']);
        if ($recipient instanceof JsonResponse) {
            return $recipient;
        }

        $feeXof = $this->computeFee((int) $data['amountXof']);

        return response()->json([
            'recipientName' => $recipient->fullName() ?? $recipient->phone_number,
            'amountXof' => $data['amountXof'],
            'feeXof' => $feeXof,
            'totalXof' => $data['amountXof'] + $feeXof,
        ]);
    }

    public function sendP2p(P2pTransferRequest $request): JsonResponse
    {
        /** @var Account $account */
        $account = $request->user();
        $data = $request->validated();
        $idempotencyKey = $request->header('Idempotency-Key');
        $amountXof = (int) $data['amountXof'];

        $recipient = $this->findRecipient($account, $data['recipientPhoneNumber']);
        if ($recipient instanceof JsonResponse) {
            return $recipient;
        }

        if ($account->kyc_status !== KycVerificationStatus::Verified) {
            return response()->json([
                'code' => 'KYC_REQUIRED',
                'message' => 'Vérification d\'identité requise pour cette opération.',
            ], 403);
        }

        if ($account->pin_hash === null || ! Hash::check($data['pin'], $account->pin_hash)) {
            return response()->json([
                'code' => 'UNAUTHORIZED',
                'message' => 'Code secret invalide.',
            ], 401);
        }

        $feeXof = $this->computeFee($amountXof);
        $totalXof = $amountXof + $feeXof;

        $existing = $this->findIdempotentTransaction($account, $idempotencyKey, [
            'amount_xof' => -$totalXof,
            'counterparty_phone_number' => $recipient->phone_number,
        ]);
        if ($existing instanceof JsonResponse) {
            return $existing;
        }
        if ($existing) {
            return response()->json((new TransactionResource($existing))->resolve(), 202);
        }

        if ($account->balance_xof < $totalXof) {
            return response()->json([
                'code' => 'INSUFFICIENT_FUNDS',
                'message' => 'Solde insuffisant pour effectuer cette opération.',
            ], 402);
        }

        $reference = 'TRF-'.strtoupper(Str::random(10));

        $transaction = DB::transaction(function () use ($account, $recipient, $amountXof, $feeXof, $totalXof, $data, $reference, $idempotencyKey) {
            $account->decrement('balance_xof', $totalXof);
            $recipient->increment('balance_xof', $amountXof);

            $recipient->transactions()->create([
                'type' => TransactionType::TransferIn,
                'status' => 'completed',
                'amount_xof' => $amountXof,
                'counterparty_name' => $account->fullName() ?? $account->phone_number,
                'counterparty_phone_number' => $account->phone_number,
                'note' => $data['note'] ?? null,
                'reference' => "{$reference}-R",
            ]);

            return $account->transactions()->create([
                'type' => TransactionType::TransferOut,
                'status' => 'completed',
                'amount_xof' => -$totalXof,
                'fee_xof' => $feeXof,
                'counterparty_name' => $recipient->fullName() ?? $recipient->phone_number,
                'counterparty_phone_number' => $recipient->phone_number,
                'note' => $data['note'] ?? null,
                'reference' => $reference,
                'idempotency_key' => $idempotencyKey,
            ]);
        });

        return response()->json((new TransactionResource($transaction))->resolve(), 202);
    }

    private function findRecipient(Account $account, string $phoneNumber): Account|JsonResponse
    {
        $recipient = Account::where('phone_number', $phoneNumber)->first();

        if (! $recipient) {
            return response()->json([
                'code' => 'RECIPIENT_NOT_FOUND',
                'message' => 'Aucun compte IPCash pour ce numéro.',
            ], 400);
        }
        if ($recipient->id === $account->id) {
            return response()->json([
                'code' => 'INVALID_RECIPIENT',
                'message' => 'Impossible de vous transférer de l\'argent à vous-même.',
            ], 400);
        }

        return $recipient;
    }

    private function computeFee(int $amountXof): int
    {
        return FeeRule::computeFor(FeeScope::P2pTransfer, $amountXof);
    }
}

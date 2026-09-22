<?php

namespace App\Http\Controllers\Api;

use App\Enums\BillProviderType;
use App\Enums\TransactionType;
use App\Http\Controllers\Api\Concerns\RecordsPendingIntegration;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\AddBillAccountRequest;
use App\Http\Requests\Payment\ConfirmQrPaymentRequest;
use App\Http\Requests\Payment\DecodeQrRequest;
use App\Http\Requests\Payment\LookupBillInvoiceRequest;
use App\Http\Requests\Payment\PayBillRequest;
use App\Http\Requests\Payment\PayMerchantRequest;
use App\Http\Requests\Payment\ResolveMerchantRequest;
use App\Http\Resources\BillAccountResource;
use App\Http\Resources\BillProviderPlanResource;
use App\Http\Resources\BillProviderResource;
use App\Http\Resources\MerchantResource;
use App\Models\Account;
use App\Models\BillProvider;
use App\Models\BillProviderPlan;
use App\Models\Merchant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;

// Implémente le tag `payments` de openapi.yaml (marchand, QR, factures).
// Aucun fournisseur n'est réellement intégré — Wave, Orange Money,
// SENELEC, Woyofal, SEN'EAU (CLAUDE.md règle 9) : chaque opération de
// paiement renvoie `INTEGRATION_PENDING`. Trois exceptions, purement
// internes (aucun mouvement d'argent, donc pas concernées par la règle
// 9) : les comptes factures enregistrés (`getBillAccounts`/
// `addBillAccount`), et la résolution d'un marchand (`resolveMerchant`)
// qui interroge réellement le répertoire admin (`MerchantResource`) —
// seul le paiement lui-même (`payMerchant`) reste en attente.
//
// Fournisseurs de factures : validés contre `BillProvider` (`is_active`),
// pas seulement contre l'enum `BillProviderType` — jusqu'ici la case
// "actif" de l'admin (BillProviderResource) n'avait aucun effet.
class PaymentController extends Controller
{
    use RecordsPendingIntegration;

    public function billProviders(): AnonymousResourceCollection
    {
        return BillProviderResource::collection(
            BillProvider::where('is_active', true)->get(),
        );
    }

    public function billPlans(string $provider): AnonymousResourceCollection|JsonResponse
    {
        $type = $this->resolveActiveProvider($provider);
        if ($type instanceof JsonResponse) {
            return $type;
        }

        return BillProviderPlanResource::collection(
            BillProviderPlan::where('bill_provider_type', $type->value)
                ->where('is_active', true)
                ->orderBy('kind')
                ->orderBy('label')
                ->get(),
        );
    }

    public function resolveMerchant(ResolveMerchantRequest $request): JsonResponse
    {
        $data = $request->validated();

        $merchant = Merchant::where('identifier_type', $data['identifierType'])
            ->where('identifier_value', $data['identifierValue'])
            ->first();

        if (! $merchant) {
            return response()->json(['code' => 'NOT_FOUND', 'message' => 'Marchand introuvable.'], 404);
        }

        return response()->json((new MerchantResource($merchant))->resolve());
    }

    public function payMerchant(PayMerchantRequest $request): JsonResponse
    {
        /** @var Account $account */
        $account = $request->user();
        $data = $request->validated();

        if ($account->pin_hash === null || ! Hash::check($data['pin'], $account->pin_hash)) {
            return response()->json(['code' => 'UNAUTHORIZED', 'message' => 'Code secret invalide.'], 401);
        }

        $this->recordPendingIntegrationAttempt(
            $account,
            TransactionType::MerchantPayment,
            -(int) $data['amountXof'],
            $request->header('Idempotency-Key'),
            'MPAY',
            ['counterparty_name' => $data['merchantId']],
        );

        return $this->integrationPending();
    }

    public function decodeQr(DecodeQrRequest $request): JsonResponse
    {
        return $this->integrationPending();
    }

    public function confirmQrPayment(ConfirmQrPaymentRequest $request): JsonResponse
    {
        /** @var Account $account */
        $account = $request->user();
        $data = $request->validated();

        if ($account->pin_hash === null || ! Hash::check($data['pin'], $account->pin_hash)) {
            return response()->json(['code' => 'UNAUTHORIZED', 'message' => 'Code secret invalide.'], 401);
        }

        $this->recordPendingIntegrationAttempt(
            $account,
            TransactionType::MerchantPayment,
            -(int) $data['amountXof'],
            $request->header('Idempotency-Key'),
            'QRPAY',
            ['note' => $data['reference'] ?? null],
        );

        return $this->integrationPending();
    }

    // Point de branchement pour un vrai prestataire : voir
    // App\Services\Bills\BillPaymentClientInterface. Renvoie toujours
    // INTEGRATION_PENDING tant que rien ne l'implémente.
    public function payBill(PayBillRequest $request): JsonResponse
    {
        /** @var Account $account */
        $account = $request->user();
        $data = $request->validated();

        if ($account->pin_hash === null || ! Hash::check($data['pin'], $account->pin_hash)) {
            return response()->json(['code' => 'UNAUTHORIZED', 'message' => 'Code secret invalide.'], 401);
        }

        if ((int) $data['amountXof'] > $account->balance_xof) {
            return response()->json(['code' => 'INSUFFICIENT_FUNDS', 'message' => 'Solde insuffisant.'], 402);
        }

        $type = $this->resolveActiveProvider($data['provider']);
        if ($type instanceof JsonResponse) {
            return $type;
        }

        $this->recordPendingIntegrationAttempt(
            $account,
            TransactionType::BillPayment,
            -(int) $data['amountXof'],
            $request->header('Idempotency-Key'),
            'BILL',
            ['counterparty_name' => $data['provider'], 'note' => $data['accountNumber']],
        );

        return $this->integrationPending();
    }

    public function getBillAccounts(Request $request, string $provider): AnonymousResourceCollection|JsonResponse
    {
        $type = $this->resolveActiveProvider($provider);
        if ($type instanceof JsonResponse) {
            return $type;
        }

        /** @var Account $account */
        $account = $request->user();

        return BillAccountResource::collection(
            $account->billAccounts()->where('bill_provider_type', $type)->get(),
        );
    }

    public function addBillAccount(AddBillAccountRequest $request, string $provider): JsonResponse
    {
        $type = $this->resolveActiveProvider($provider);
        if ($type instanceof JsonResponse) {
            return $type;
        }

        /** @var Account $account */
        $account = $request->user();
        $billAccount = $account->billAccounts()->create([
            'bill_provider_type' => $type,
            'nickname' => $request->validated('nickname'),
            'account_number' => $request->validated('accountNumber'),
        ]);

        return response()->json((new BillAccountResource($billAccount))->resolve(), 201);
    }

    // Point de branchement pour un vrai prestataire : voir
    // App\Services\Bills\BillPaymentClientInterface. Renvoie toujours
    // INTEGRATION_PENDING tant que rien ne l'implémente.
    public function lookupBillInvoice(LookupBillInvoiceRequest $request, string $provider): JsonResponse
    {
        $type = $this->resolveActiveProvider($provider);
        if ($type instanceof JsonResponse) {
            return $type;
        }

        return $this->integrationPending();
    }

    // Valide à la fois la forme (enum `BillProviderType`) et le fond (ligne
    // `BillProvider` active dans l'admin) — un fournisseur retiré du
    // catalogue admin doit se comporter comme un fournisseur inconnu.
    private function resolveActiveProvider(string $provider): BillProviderType|JsonResponse
    {
        $type = BillProviderType::tryFrom($provider);
        if (! $type || ! BillProvider::where('type', $type->value)->where('is_active', true)->exists()) {
            return $this->unknownProvider();
        }

        return $type;
    }

    private function unknownProvider(): JsonResponse
    {
        return response()->json(['code' => 'NOT_FOUND', 'message' => 'Fournisseur de factures inconnu.'], 404);
    }

    private function integrationPending(): JsonResponse
    {
        return response()->json([
            'code' => 'INTEGRATION_PENDING',
            'message' => 'Aucun prestataire réel n\'est encore connecté pour cette opération.',
        ], 400);
    }
}

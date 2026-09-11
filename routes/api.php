<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BeneficiaryController;
use App\Http\Controllers\Api\CardController;
use App\Http\Controllers\Api\CashioController;
use App\Http\Controllers\Api\CreditController;
use App\Http\Controllers\Api\EsimController;
use App\Http\Controllers\Api\ForeignBalanceController;
use App\Http\Controllers\Api\InsuranceController;
use App\Http\Controllers\Api\KycController;
use App\Http\Controllers\Api\OrangeMoneyWebhookController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PocketController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ProviderController;
use App\Http\Controllers\Api\SupportController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\TransferController;
use App\Http\Controllers\Api\WaveWebhookController;
use Illuminate\Support\Facades\Route;

// Chemins alignés sur api/openapi.yaml (source de vérité, voir CLAUDE.md
// côté Flutter). Un groupe par tag du contrat.
Route::prefix('auth')->group(function () {
    Route::post('register/phone', [AuthController::class, 'registerPhone'])
        ->middleware('throttle:6,1');
    Route::post('otp/verify', [AuthController::class, 'verifyOtp']);
    Route::post('pin', [AuthController::class, 'createPin'])
        ->middleware('session.token');
    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1');
    Route::post('refresh', [AuthController::class, 'refresh']);

    Route::middleware(['auth:sanctum', 'account.notBlocked'])->group(function () {
        Route::post('register/personal-info', [AuthController::class, 'personalInfo']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

Route::prefix('kyc')->middleware(['auth:sanctum', 'account.notBlocked'])->group(function () {
    Route::post('document/analyze', [KycController::class, 'analyzeDocument']);
    Route::post('face/verify', [KycController::class, 'verifyFace']);
    Route::post('documents', [KycController::class, 'submitDocuments']);
    Route::get('status', [KycController::class, 'status']);
});

Route::middleware(['auth:sanctum', 'account.notBlocked'])->group(function () {
    Route::prefix('accounts/me')->group(function () {
        Route::get('/', [AccountController::class, 'me']);
        Route::get('balance', [AccountController::class, 'balance']);
    });

    Route::prefix('transactions')->group(function () {
        Route::get('/', [TransactionController::class, 'index']);
        Route::get('{id}', [TransactionController::class, 'show']);
    });

    Route::get('providers/mobile-money', [ProviderController::class, 'mobileMoney']);
    Route::get('providers/financial-institutions', [ProviderController::class, 'financialInstitutions']);
    Route::get('providers/countries', [ProviderController::class, 'countries']);

    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show']);
        Route::patch('/', [ProfileController::class, 'update']);
        Route::get('sessions', [ProfileController::class, 'sessions']);
        Route::delete('sessions/{id}', [ProfileController::class, 'revokeSession']);
    });

    Route::prefix('transfers')->group(function () {
        Route::post('quote', [TransferController::class, 'quote']);
        Route::post('p2p', [TransferController::class, 'sendP2p'])->middleware('idempotency');
    });

    Route::middleware('idempotency')->group(function () {
        Route::post('cashin', [CashioController::class, 'cashin']);
        Route::post('cashin/card', [CashioController::class, 'cashinCard']);
        Route::post('cashin/bank', [CashioController::class, 'cashinBank']);
        Route::post('cashin/paypal', [CashioController::class, 'cashinPaypal']);
        Route::post('cashout', [CashioController::class, 'cashout']);

        Route::prefix('foreign-balances')->group(function () {
            Route::post('convert-from-xof', [ForeignBalanceController::class, 'convertFromXof']);
            Route::post('convert-to-xof', [ForeignBalanceController::class, 'convertToXof']);
            Route::post('pay-merchant', [ForeignBalanceController::class, 'payMerchant']);
            Route::post('send', [ForeignBalanceController::class, 'send']);
        });
    });
    Route::get('foreign-balances', [ForeignBalanceController::class, 'index']);

    Route::prefix('cards')->group(function () {
        Route::get('/', [CardController::class, 'index']);
        Route::get('ephemeral-card', [CardController::class, 'getEphemeralCard']);
        Route::delete('ephemeral-card', [CardController::class, 'destroyEphemeralCard']);
        Route::post('ephemeral-card/recharge', [CardController::class, 'rechargeEphemeralCard'])
            ->middleware('idempotency');
        Route::post('ephemeral-card/transfer-out', [CardController::class, 'transferOutEphemeralCard'])
            ->middleware('idempotency');
        Route::delete('{id}', [CardController::class, 'destroy'])->middleware('idempotency');
        Route::post('{id}/freeze', [CardController::class, 'freeze']);
        Route::post('{id}/unfreeze', [CardController::class, 'unfreeze']);
        Route::post('{id}/reveal', [CardController::class, 'reveal']);
        Route::post('{id}/ephemeral-card', [CardController::class, 'generateEphemeralCard'])
            ->middleware('idempotency');
        Route::post('{id}/recharge', [CardController::class, 'recharge'])->middleware('idempotency');
        Route::post('{id}/transfer-out', [CardController::class, 'transferOut'])->middleware('idempotency');
        Route::get('{id}/transactions', [CardController::class, 'transactions']);
    });

    Route::prefix('pockets')->group(function () {
        Route::get('/', [PocketController::class, 'index']);
        Route::post('/', [PocketController::class, 'store']);
        Route::patch('{id}', [PocketController::class, 'update']);
        Route::delete('{id}', [PocketController::class, 'destroy']);
        Route::post('{id}/transfer-in', [PocketController::class, 'transferIn'])->middleware('idempotency');
        Route::post('{id}/transfer-out', [PocketController::class, 'transferOut'])->middleware('idempotency');
        Route::post('{id}/lock', [PocketController::class, 'lock']);
        Route::post('{id}/unlock', [PocketController::class, 'unlock']);
        Route::get('{id}/transactions', [PocketController::class, 'transactions']);
    });

    Route::prefix('beneficiaries')->group(function () {
        Route::get('/', [BeneficiaryController::class, 'index']);
        Route::post('/', [BeneficiaryController::class, 'store']);
    });

    Route::prefix('credit')->group(function () {
        Route::get('operators', [CreditController::class, 'operators']);
        Route::post('purchase', [CreditController::class, 'purchase'])->middleware('idempotency');
    });

    Route::prefix('insurance')->group(function () {
        Route::get('plans', [InsuranceController::class, 'plans']);
        Route::post('purchase', [InsuranceController::class, 'purchase'])->middleware('idempotency');
    });

    Route::prefix('esim')->group(function () {
        Route::get('plans', [EsimController::class, 'plans']);
        Route::post('activate', [EsimController::class, 'activate'])->middleware('idempotency');
    });

    Route::prefix('payments')->group(function () {
        Route::post('merchants/resolve', [PaymentController::class, 'resolveMerchant']);
        Route::post('merchants/pay', [PaymentController::class, 'payMerchant'])->middleware('idempotency');
        Route::post('qr/decode', [PaymentController::class, 'decodeQr']);
        Route::post('qr/confirm', [PaymentController::class, 'confirmQrPayment'])->middleware('idempotency');
        Route::get('bills/providers', [PaymentController::class, 'billProviders']);
        Route::post('bills/pay', [PaymentController::class, 'payBill'])->middleware('idempotency');
        Route::get('bills/{provider}/accounts', [PaymentController::class, 'getBillAccounts']);
        Route::post('bills/{provider}/accounts', [PaymentController::class, 'addBillAccount']);
        Route::post('bills/{provider}/invoice', [PaymentController::class, 'lookupBillInvoice']);
    });

    Route::get('support/config', [SupportController::class, 'config']);
    Route::post('support/chat', [SupportController::class, 'chat']);
});

// Appelé serveur-à-serveur par Orange (OM Pay), jamais par l'app — pas de
// token bearer disponible pour cet appel, doit rester hors du groupe
// `auth:sanctum` ci-dessus.
Route::post('webhooks/orange-money', [OrangeMoneyWebhookController::class, 'handle'])
    ->name('orange-money.webhook');

// Idem pour Wave — sa signature (`Wave-Signature`) est vérifiée dans le
// contrôleur lui-même plutôt que via un middleware, faute d'un moyen
// standard de lire le corps brut avant que Laravel ne le parse ailleurs.
Route::post('webhooks/wave', [WaveWebhookController::class, 'handle'])
    ->name('wave.webhook');

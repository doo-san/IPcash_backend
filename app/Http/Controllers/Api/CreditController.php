<?php

namespace App\Http\Controllers\Api;

use App\Enums\TransactionType;
use App\Http\Controllers\Api\Concerns\RecordsPendingIntegration;
use App\Http\Controllers\Controller;
use App\Http\Requests\Credit\CreditPurchaseRequest;
use App\Http\Resources\CreditOperatorResource;
use App\Models\Account;
use App\Models\CreditOperator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;

// Implémente le tag `credit` de openapi.yaml (achat de crédit/forfait
// télécom). Aucun opérateur n'est réellement intégré (CLAUDE.md règle 9,
// « achat de crédit ») : `purchase` renvoie toujours `INTEGRATION_PENDING`
// après avoir vérifié PIN et solde — un échec honnête plutôt qu'une
// fausse réussite.
class CreditController extends Controller
{
    use RecordsPendingIntegration;

    public function operators(): AnonymousResourceCollection
    {
        return CreditOperatorResource::collection(
            CreditOperator::where('is_available', true)->get(),
        );
    }

    // Point de branchement pour un vrai prestataire : voir
    // App\Services\Credit\CreditTopUpClientInterface. Renvoie toujours
    // INTEGRATION_PENDING tant que rien ne l'implémente.
    public function purchase(CreditPurchaseRequest $request): JsonResponse
    {
        /** @var Account $account */
        $account = $request->user();
        $data = $request->validated();

        if ($account->pin_hash === null || ! Hash::check($data['pin'], $account->pin_hash)) {
            return response()->json(['code' => 'UNAUTHORIZED', 'message' => 'Code secret invalide.'], 401);
        }

        $amountXof = (int) $data['amountXof'];

        if ($amountXof > $account->balance_xof) {
            return response()->json(['code' => 'INSUFFICIENT_FUNDS', 'message' => 'Solde insuffisant.'], 402);
        }

        $this->recordPendingIntegrationAttempt(
            $account,
            TransactionType::CreditPurchase,
            -$amountXof,
            $request->header('Idempotency-Key'),
            'CREDIT',
            ['counterparty_phone_number' => $data['phoneNumber']],
        );

        return response()->json([
            'code' => 'INTEGRATION_PENDING',
            'message' => 'Aucun opérateur télécom n\'est encore connecté pour cette opération.',
        ], 400);
    }
}

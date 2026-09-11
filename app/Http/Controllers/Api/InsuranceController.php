<?php

namespace App\Http\Controllers\Api;

use App\Enums\FeeScope;
use App\Enums\TransactionType;
use App\Http\Controllers\Api\Concerns\RecordsPendingIntegration;
use App\Http\Controllers\Controller;
use App\Http\Requests\Insurance\InsurancePurchaseRequest;
use App\Http\Resources\InsurancePlanResource;
use App\Models\Account;
use App\Models\FeeRule;
use App\Models\InsurancePlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;

// Implémente le tag `insurance` de openapi.yaml. `plans` expose le
// catalogue admin (voir migration `insurance_plans`) — jusqu'ici sans
// aucun effet réel, `InsuranceDurationScreen` restant câblé sur
// `Money.zero`. Aucun assureur n'est réellement intégré (CLAUDE.md
// règle 9) : `purchase` renvoie toujours `INTEGRATION_PENDING` après
// vérification PIN/solde. Les frais (FeeRule, scope `insurance`, 0 par
// défaut) sont pris en compte dès maintenant dans la vérification de
// solde ; ils seront réellement débités le jour où un assureur sera
// branché.
class InsuranceController extends Controller
{
    use RecordsPendingIntegration;

    public function plans(): AnonymousResourceCollection
    {
        return InsurancePlanResource::collection(
            InsurancePlan::where('is_active', true)->get(),
        );
    }

    public function purchase(InsurancePurchaseRequest $request): JsonResponse
    {
        /** @var Account $account */
        $account = $request->user();
        $data = $request->validated();

        if ($account->pin_hash === null || ! Hash::check($data['pin'], $account->pin_hash)) {
            return response()->json(['code' => 'UNAUTHORIZED', 'message' => 'Code secret invalide.'], 401);
        }

        $amountXof = (int) $data['amountXof'];
        $feeXof = FeeRule::computeFor(FeeScope::Insurance, $amountXof);

        if ($amountXof + $feeXof > $account->balance_xof) {
            return response()->json(['code' => 'INSUFFICIENT_FUNDS', 'message' => 'Solde insuffisant.'], 402);
        }

        $this->recordPendingIntegrationAttempt(
            $account,
            TransactionType::InsurancePurchase,
            -$amountXof,
            $request->header('Idempotency-Key'),
            'INSUR',
            ['note' => $data['plateNumber']],
        );

        return response()->json([
            'code' => 'INTEGRATION_PENDING',
            'message' => 'Aucun assureur n\'est encore connecté pour cette opération.',
        ], 400);
    }
}

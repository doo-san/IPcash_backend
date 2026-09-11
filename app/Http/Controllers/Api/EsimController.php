<?php

namespace App\Http\Controllers\Api;

use App\Enums\TransactionType;
use App\Http\Controllers\Api\Concerns\RecordsPendingIntegration;
use App\Http\Controllers\Controller;
use App\Http\Requests\Esim\EsimActivateRequest;
use App\Http\Resources\EsimPlanResource;
use App\Models\Account;
use App\Models\EsimPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;

// Implémente le tag `esim` de openapi.yaml. `plans` expose le catalogue
// admin (prix par palier scope × data, voir migration `esim_plans`) —
// jusqu'ici calculés en dur côté client. Aucun opérateur eSIM n'est
// réellement intégré (CLAUDE.md règle 9) : `activate` renvoie toujours
// `INTEGRATION_PENDING` après vérification PIN/solde.
class EsimController extends Controller
{
    use RecordsPendingIntegration;

    public function plans(): AnonymousResourceCollection
    {
        return EsimPlanResource::collection(
            EsimPlan::where('is_active', true)->get(),
        );
    }

    public function activate(EsimActivateRequest $request): JsonResponse
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
            TransactionType::EsimActivation,
            -$amountXof,
            $request->header('Idempotency-Key'),
            'ESIM',
            ['counterparty_name' => $data['countryName'], 'note' => $data['planLabel']],
        );

        return response()->json([
            'code' => 'INTEGRATION_PENDING',
            'message' => 'Aucun opérateur eSIM n\'est encore connecté pour cette opération.',
        ], 400);
    }
}

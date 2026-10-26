<?php

namespace App\Http\Controllers\Api;

use App\Enums\FeeScope;
use App\Enums\InsurancePolicyStatus;
use App\Enums\KycVerificationStatus;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Exceptions\InsufficientFundsException;
use App\Http\Controllers\Api\Concerns\HandlesIdempotency;
use App\Http\Controllers\Api\Concerns\LocksRowsForUpdate;
use App\Http\Controllers\Controller;
use App\Http\Requests\Insurance\InsurancePurchaseRequest;
use App\Http\Resources\InsurancePlanResource;
use App\Http\Resources\InsurancePolicyResource;
use App\Models\Account;
use App\Models\FeeRule;
use App\Models\InsurancePlan;
use App\Models\InsurancePolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

// Implémente le tag `insurance` de openapi.yaml. IPCash émet lui-même
// l'attestation (décision produit, voir l'exception de la règle 9 dans
// CLAUDE.md côté Flutter) : la souscription débite le solde et crée un
// contrat `pending` ; un `super_admin` le valide (attestation PDF générée)
// ou le rejette (remboursement) depuis `InsurancePolicyResource`, voir
// `InsurancePolicyService`. Le prix vient toujours du catalogue, jamais
// du client.
class InsuranceController extends Controller
{
    use HandlesIdempotency;
    use LocksRowsForUpdate;

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
        $idempotencyKey = $request->header('Idempotency-Key');

        if ($account->kyc_status !== KycVerificationStatus::Verified) {
            return response()->json([
                'code' => 'KYC_REQUIRED',
                'message' => 'Vérification d\'identité requise pour cette opération.',
            ], 403);
        }

        if ($account->pin_hash === null || ! Hash::check($data['pin'], $account->pin_hash)) {
            return response()->json(['code' => 'UNAUTHORIZED', 'message' => 'Code secret invalide.'], 401);
        }

        $plan = InsurancePlan::where('is_active', true)
            ->where('insurance_type', $data['type'])
            ->where('duration_months', $data['durationMonths'])
            ->first();
        if (! $plan) {
            throw ValidationException::withMessages([
                'durationMonths' => 'Cette formule n\'est plus disponible.',
            ]);
        }

        $feeXof = FeeRule::computeFor(FeeScope::Insurance, $plan->price_xof);
        $totalXof = $plan->price_xof + $feeXof;

        $existing = $this->findIdempotentTransaction($account, $idempotencyKey, [
            'type' => TransactionType::InsurancePurchase,
            'amount_xof' => -$totalXof,
        ]);
        if ($existing instanceof JsonResponse) {
            return $existing;
        }
        if ($existing) {
            $policy = InsurancePolicy::where('purchase_transaction_id', $existing->id)->first();

            return $policy
                ? response()->json((new InsurancePolicyResource($policy))->resolve(), 202)
                : response()->json([
                    'code' => 'IDEMPOTENCY_CONFLICT',
                    'message' => 'Cette clé d\'idempotence a déjà été utilisée avec des paramètres différents.',
                ], 409);
        }

        try {
            $policy = DB::transaction(function () use ($account, $data, $plan, $feeXof, $totalXof, $idempotencyKey) {
                $account = $this->lockForUpdate($account);
                if ($account->balance_xof < $totalXof) {
                    throw new InsufficientFundsException;
                }

                $account->decrement('balance_xof', $totalXof);

                $transaction = $account->transactions()->create([
                    'type' => TransactionType::InsurancePurchase,
                    'status' => TransactionStatus::Completed,
                    'amount_xof' => -$totalXof,
                    'fee_xof' => $feeXof,
                    'note' => $data['plateNumber'],
                    'reference' => 'INSUR-'.strtoupper(Str::random(10)),
                    'idempotency_key' => $idempotencyKey,
                    'main_balance_after_xof' => $account->balance_xof,
                ]);

                $policy = new InsurancePolicy([
                    'insurance_type' => $plan->insurance_type,
                    'duration_months' => $plan->duration_months,
                    'price_xof' => $plan->price_xof,
                    'fee_xof' => $feeXof,
                    'plate_number' => $data['plateNumber'],
                    'vehicle_brand' => $data['vehicleBrand'] ?? null,
                    'vehicle_usage' => $data['vehicleUsage'],
                    'horsepower' => $data['horsepower'] ?? null,
                    'seats' => $data['seats'] ?? null,
                    'age_bracket' => $data['ageBracket'] ?? null,
                    'displacement_cc' => $data['displacementCc'] ?? null,
                    'body_type' => $data['bodyType'] ?? null,
                ]);
                $policy->account_id = $account->id;
                $policy->status = InsurancePolicyStatus::Pending;
                $policy->purchase_transaction_id = $transaction->id;
                $policy->save();

                return $policy;
            });
        } catch (InsufficientFundsException) {
            return response()->json([
                'code' => 'INSUFFICIENT_FUNDS',
                'message' => 'Solde insuffisant pour effectuer cette opération.',
            ], 402);
        }

        return response()->json((new InsurancePolicyResource($policy))->resolve(), 202);
    }

    public function policies(Request $request): AnonymousResourceCollection
    {
        /** @var Account $account */
        $account = $request->user();

        return InsurancePolicyResource::collection(
            $account->insurancePolicies()->latest()->get(),
        );
    }

    public function show(Request $request, string $id): JsonResponse
    {
        /** @var Account $account */
        $account = $request->user();
        $policy = $account->insurancePolicies()->findOrFail($id);

        return response()->json((new InsurancePolicyResource($policy))->resolve());
    }

    public function attestation(Request $request, string $id): StreamedResponse|JsonResponse
    {
        /** @var Account $account */
        $account = $request->user();
        $policy = $account->insurancePolicies()->findOrFail($id);

        if ($policy->status !== InsurancePolicyStatus::Active || $policy->attestation_path === null) {
            return response()->json([
                'code' => 'ATTESTATION_NOT_AVAILABLE',
                'message' => 'L\'attestation n\'est disponible qu\'une fois le contrat validé.',
            ], 404);
        }

        return Storage::disk('insurance')->download(
            $policy->attestation_path,
            "attestation-{$policy->policy_number}.pdf",
            ['Content-Type' => 'application/pdf', 'Cache-Control' => 'no-store'],
        );
    }
}

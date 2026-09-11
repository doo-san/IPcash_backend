<?php

namespace App\Http\Controllers\Api;

use App\Enums\KycVerificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Kyc\AnalyzeDocumentRequest;
use App\Http\Requests\Kyc\SubmitDocumentsRequest;
use App\Http\Requests\Kyc\VerifyFaceRequest;
use App\Models\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// Implémente le tag `kyc` de openapi.yaml. Revue 100% manuelle (décision
// produit) : /kyc/documents place toujours le compte en `pending` ou
// `review` (jamais auto-validé) — la décision finale se prend dans
// l'admin, voir `KycDocumentResource` (actions Approuver/Rejeter).
class KycController extends Controller
{
    // Stockage sur le disque "public" pour l'instant, comme documenté dans
    // `KycDocumentResource` : à déplacer sur un disque privé avec URLs
    // signées avant la mise en production (données KYC sensibles).
    private const DISK = 'public';

    public function analyzeDocument(AnalyzeDocumentRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Aucun fournisseur OCR réel n'est intégré (CLAUDE.md règle 9) —
        // comme `KycFakeDataSource.analyzeDocument` côté Flutter, on
        // renvoie exactement la saisie de l'utilisateur : le chemin de
        // code pour une anomalie détectée existe côté client, mais rien
        // ici ne peut jamais le déclencher tant qu'un vrai prestataire
        // n'est pas branché. Les fichiers ne sont pas conservés à cette
        // étape purement informative (seule /kyc/documents persiste).
        return response()->json([
            'nameMatches' => true,
            'extractedFirstName' => $data['typedFirstName'],
            'extractedLastName' => $data['typedLastName'],
        ]);
    }

    public function verifyFace(VerifyFaceRequest $request): JsonResponse
    {
        // Même principe : aucun fournisseur face-match/liveness réel
        // (voir KycFakeDataSource.verifyFace côté Flutter).
        return response()->json([
            'matchScore' => 0.97,
            'livenessPassed' => true,
            'passed' => true,
        ]);
    }

    public function submitDocuments(SubmitDocumentsRequest $request): JsonResponse
    {
        /** @var Account $account */
        $account = $request->user();
        $data = $request->validated();
        $hasAnomaly = (bool) ($data['hasClientSideAnomaly'] ?? false);

        $frontPath = $request->file('front')->store("kyc/{$account->id}", self::DISK);
        $backPath = $request->hasFile('back')
            ? $request->file('back')->store("kyc/{$account->id}", self::DISK)
            : null;
        $selfiePath = $request->file('selfie')->store("kyc/{$account->id}", self::DISK);

        $status = $hasAnomaly ? KycVerificationStatus::Review : KycVerificationStatus::Pending;

        $account->kycDocuments()->create([
            'document_type' => $data['documentType'],
            'front_path' => $frontPath,
            'back_path' => $backPath,
            'selfie_path' => $selfiePath,
            'has_client_side_anomaly' => $hasAnomaly,
            'status' => $status,
            'submitted_at' => now(),
        ]);

        $account->update(['kyc_status' => $status, 'kyc_rejection_reason' => null]);

        return response()->json([
            'status' => $status->value,
            'rejectionReason' => null,
            'submittedAt' => now()->toIso8601String(),
        ], 202);
    }

    public function status(Request $request): JsonResponse
    {
        /** @var Account $account */
        $account = $request->user();
        // `->first()` (pas `->value()`) : on veut l'attribut casté en
        // Carbon par le modèle, pas la valeur brute de la colonne.
        $latestDocument = $account->kycDocuments()->latest('submitted_at')->first();

        return response()->json([
            'status' => $account->kyc_status->value,
            'rejectionReason' => $account->kyc_rejection_reason,
            'submittedAt' => $latestDocument?->submitted_at->toIso8601String(),
        ]);
    }
}

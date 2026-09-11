<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// Couvre /kyc/* de bout en bout — voir api/openapi.yaml. Aucun fournisseur
// OCR/face-match/liveness réel n'est intégré (CLAUDE.md règle 9) : analyse
// et vérification faciale échotent toujours la saisie/un succès simulé.
class KycFlowTest extends TestCase
{
    use RefreshDatabase;

    private Account $account;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->account = Account::create(['phone_number' => '+221771234567']);
    }

    private function authHeader(): array
    {
        $token = $this->account->createToken('test')->plainTextToken;

        return ['Authorization' => "Bearer {$token}"];
    }

    public function test_analyze_document_echoes_typed_name_without_persisting_files(): void
    {
        $response = $this->postJson('/api/kyc/document/analyze', [
            'typedFirstName' => 'Abdou',
            'typedLastName' => 'Ba',
            'front' => UploadedFile::fake()->image('front.jpg'),
        ], $this->authHeader());

        $response->assertOk()->assertJson([
            'nameMatches' => true,
            'extractedFirstName' => 'Abdou',
            'extractedLastName' => 'Ba',
        ]);

        Storage::disk('public')->assertDirectoryEmpty('kyc');
    }

    public function test_verify_face_returns_simulated_success(): void
    {
        $response = $this->postJson('/api/kyc/face/verify', [
            'selfie' => UploadedFile::fake()->image('selfie.jpg'),
            'documentFront' => UploadedFile::fake()->image('front.jpg'),
        ], $this->authHeader());

        $response->assertOk()->assertJson([
            'matchScore' => 0.97,
            'livenessPassed' => true,
            'passed' => true,
        ]);
    }

    public function test_submit_documents_stores_files_and_sets_pending_status(): void
    {
        $response = $this->postJson('/api/kyc/documents', [
            'documentType' => 'nationalId',
            'front' => UploadedFile::fake()->image('front.jpg'),
            'selfie' => UploadedFile::fake()->image('selfie.jpg'),
        ], $this->authHeader());

        $response->assertStatus(202)->assertJson(['status' => 'pending']);

        $this->account->refresh();
        $this->assertSame('pending', $this->account->kyc_status->value);

        $document = $this->account->kycDocuments()->first();
        Storage::disk('public')->assertExists($document->front_path);
        Storage::disk('public')->assertExists($document->selfie_path);
    }

    // Régression : `postJson()` ci-dessus encode les champs non-fichiers en
    // JSON (vrai booléen PHP préservé), contrairement à l'app réelle qui
    // envoie un vrai `multipart/form-data` (dio `FormData`) où
    // `hasClientSideAnomaly` arrive forcément comme la chaîne "false"/"true"
    // — ce que la règle `boolean` de Laravel rejetait avant
    // `SubmitDocumentsRequest::prepareForValidation()`. `->post()` (pas
    // `->postJson()`) reproduit ce multipart réel.
    public function test_submit_documents_accepts_a_stringified_boolean_from_a_real_multipart_request(): void
    {
        $response = $this->post('/api/kyc/documents', [
            'documentType' => 'nationalId',
            'front' => UploadedFile::fake()->image('front.jpg'),
            'selfie' => UploadedFile::fake()->image('selfie.jpg'),
            'hasClientSideAnomaly' => 'false',
        ], $this->authHeader());

        $response->assertStatus(202)->assertJson(['status' => 'pending']);
        $this->assertSame('pending', $this->account->fresh()->kyc_status->value);
    }

    public function test_submit_documents_with_anomaly_sets_review_status(): void
    {
        $this->postJson('/api/kyc/documents', [
            'documentType' => 'passport',
            'front' => UploadedFile::fake()->image('front.jpg'),
            'selfie' => UploadedFile::fake()->image('selfie.jpg'),
            'hasClientSideAnomaly' => true,
        ], $this->authHeader())->assertStatus(202)->assertJson(['status' => 'review']);

        $this->assertSame('review', $this->account->fresh()->kyc_status->value);
    }

    public function test_status_reflects_account_state(): void
    {
        $this->getJson('/api/kyc/status', $this->authHeader())
            ->assertOk()
            ->assertJson(['status' => 'notStarted', 'submittedAt' => null]);

        $this->postJson('/api/kyc/documents', [
            'documentType' => 'nationalId',
            'front' => UploadedFile::fake()->image('front.jpg'),
            'selfie' => UploadedFile::fake()->image('selfie.jpg'),
        ], $this->authHeader());

        $status = $this->getJson('/api/kyc/status', $this->authHeader())->assertOk()->json();
        $this->assertSame('pending', $status['status']);
        $this->assertNotNull($status['submittedAt']);
    }
}

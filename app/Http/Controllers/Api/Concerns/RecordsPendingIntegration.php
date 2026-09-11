<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Account;
use Illuminate\Support\Str;

// Toute opération qui s'arrête sur `INTEGRATION_PENDING` (aucun prestataire
// tiers branché — CLAUDE.md règle 9) laisse quand même une trace dans le
// grand livre : une `Transaction` en statut `failed` avec le motif, pour
// que l'admin (« Transactions ») ait la liste complète des tentatives.
// Jamais une fausse réussite — le montant n'est jamais appliqué à un
// solde, le statut est explicitement `failed`.
trait RecordsPendingIntegration
{
    protected function recordPendingIntegrationAttempt(
        Account $account,
        TransactionType $type,
        int $amountXof,
        ?string $idempotencyKey,
        string $referencePrefix,
        array $context = [],
    ): void {
        // Un rejeu de la même clé d'idempotence ne recrée pas la ligne.
        if ($idempotencyKey !== null
            && $account->transactions()->where('idempotency_key', $idempotencyKey)->exists()
        ) {
            return;
        }

        $account->transactions()->create([
            'type' => $type,
            'status' => TransactionStatus::Failed,
            'amount_xof' => $amountXof,
            'failure_reason' => 'Aucun prestataire réel n\'est encore connecté pour cette opération.',
            'reference' => $referencePrefix.'-'.strtoupper(Str::random(10)),
            'idempotency_key' => $idempotencyKey,
            ...$context,
        ]);
    }
}

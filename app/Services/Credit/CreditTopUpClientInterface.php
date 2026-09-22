<?php

namespace App\Services\Credit;

use App\Models\CreditOperator;

// Point de branchement pour un vrai opérateur de recharge crédit (Orange,
// Free, Expresso). Aucune implémentation n'existe encore — le catalogue
// (CreditOperator), l'admin (CreditOperatorResource, avec ses champs
// api_base_url/api_key/api_secret déjà chiffrés) et la route/l'écran
// mobile sont prêts, seule cette interface reste à implémenter une fois
// un vrai prestataire choisi. CreditController::purchase() renvoie
// INTEGRATION_PENDING tant que rien ne l'implémente (CLAUDE.md règle 9 —
// jamais de faux succès pour une intégration qui n'existe pas).
//
// Pour brancher un vrai prestataire :
// 1. Implémenter cette interface — voir WaveClient/OrangeMoneyClient
//    (app/Services/Wave, app/Services/OrangeMoney) pour le style attendu :
//    Http::baseUrl($operator->api_base_url), authentification à partir de
//    $operator->api_key/api_secret (déjà déchiffrés par le cast
//    `encrypted` du modèle).
// 2. Lier l'implémentation dans un ServiceProvider, ex. :
//    $this->app->bind(CreditTopUpClientInterface::class, MonClient::class);
// 3. Dans CreditController::purchase(), résoudre l'interface seulement
//    quand $operator->isConfigured() est vrai ET que la liaison existe
//    (app()->bound(self::class)) — sinon garder le repli
//    INTEGRATION_PENDING actuel, opérateur par opérateur (chacun peut
//    être branché indépendamment des autres).
//
// Contrairement aux factures, il n'existe aujourd'hui aucun catalogue de
// forfaits par opérateur (pas d'équivalent BillProviderPlan) — la
// recharge se fait à montant libre (voir CreditPurchaseRequest). Si un
// vrai prestataire n'accepte que des montants/bundles fixes, ce sera à
// ajouter à ce moment-là plutôt qu'anticipé ici sans contrat réel.
interface CreditTopUpClientInterface
{
    /**
     * Recharge un numéro pour de vrai — déplace de l'argent, n'est jamais
     * appelé sans confirmation PIN préalable (voir
     * CreditController::purchase()) ni sans clé d'idempotence.
     *
     * @return array{providerReference: string, status: 'completed'|'failed'|'processing'}
     */
    public function purchase(
        CreditOperator $operator,
        string $phoneNumber,
        int $amountXof,
        string $idempotencyKey,
    ): array;
}

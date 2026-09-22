<?php

namespace App\Services\Bills;

use App\Models\BillProvider;

// Point de branchement pour un vrai fournisseur de factures (SENELEC,
// Woyofal, SEN'EAU, Canal+, Rapido). Aucune implémentation n'existe
// encore — le catalogue (BillProvider, BillProviderPlan), l'admin
// (BillProviderResource, avec ses champs api_base_url/api_key/api_secret
// déjà chiffrés) et la route/l'écran mobile sont prêts, seule cette
// interface reste à implémenter une fois un vrai prestataire choisi.
// PaymentController::payBill()/lookupBillInvoice() renvoient
// INTEGRATION_PENDING tant que rien ne l'implémente (CLAUDE.md règle 9 —
// jamais de faux succès pour une intégration qui n'existe pas).
//
// Pour brancher un vrai prestataire :
// 1. Implémenter cette interface — voir WaveClient/OrangeMoneyClient
//    (app/Services/Wave, app/Services/OrangeMoney) pour le style attendu :
//    Http::baseUrl($provider->api_base_url), authentification à partir de
//    $provider->api_key/api_secret (déjà déchiffrés par le cast
//    `encrypted` du modèle).
// 2. Lier l'implémentation dans un ServiceProvider, ex. :
//    $this->app->bind(BillPaymentClientInterface::class, MonClient::class);
// 3. Dans PaymentController, résoudre l'interface seulement quand
//    $provider->isConfigured() est vrai ET que la liaison existe
//    (app()->bound(self::class)) — sinon garder le repli
//    INTEGRATION_PENDING actuel, provider par provider (chacun peut être
//    branché indépendamment des autres).
interface BillPaymentClientInterface
{
    /**
     * Consulte le montant dû avant paiement (ex. relevé de compteur
     * Woyofal/SEN'EAU, solde de police SENELEC) — n'existe pas pour un
     * fournisseur à formules fixes (Canal+, voir BillProviderPlan).
     *
     * @return array{amountXof: int, dueDate: ?string, reference: string}
     */
    public function lookupInvoice(BillProvider $provider, string $accountNumber): array;

    /**
     * Règle une facture pour de vrai — déplace de l'argent, n'est jamais
     * appelé sans confirmation PIN préalable (voir
     * PaymentController::payBill()) ni sans clé d'idempotence.
     *
     * @return array{providerReference: string, status: 'completed'|'failed'|'processing'}
     */
    public function payBill(
        BillProvider $provider,
        string $accountNumber,
        int $amountXof,
        string $idempotencyKey,
    ): array;
}

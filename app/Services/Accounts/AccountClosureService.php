<?php

namespace App\Services\Accounts;

use App\Enums\InsurancePolicyStatus;
use App\Enums\TransactionStatus;
use App\Models\Account;
use App\Models\EphemeralCard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

// Clôture d'un compte client (demandée depuis l'app ou par un admin).
// Effet immédiat : accès coupé et données non obligatoires effacées. Ce que
// la réglementation anti-blanchiment impose de garder (identité, numéro,
// pièces KYC, transactions, contrats d'assurance) reste conservé, réservé à
// l'admin, puis supprimé par `accounts:purge-closed` après la durée légale
// (config/accounts.php).
class AccountClosureService
{
    public const MAIN_BALANCE = 'MAIN_BALANCE';

    public const POCKET_BALANCE = 'POCKET_BALANCE';

    public const CARD_BALANCE = 'CARD_BALANCE';

    public const EPHEMERAL_CARD_BALANCE = 'EPHEMERAL_CARD_BALANCE';

    public const FOREIGN_BALANCE = 'FOREIGN_BALANCE';

    public const PENDING_TRANSACTION = 'PENDING_TRANSACTION';

    public const PENDING_INSURANCE = 'PENDING_INSURANCE';

    /**
     * Ce qui empêche la clôture : aucun argent ne doit rester sur le compte,
     * et aucune opération ne doit être en cours.
     *
     * @return list<string>
     */
    public function blockers(Account $account): array
    {
        $checks = [
            self::MAIN_BALANCE => $account->balance_xof !== 0,
            self::POCKET_BALANCE => $account->pockets()->where('balance_xof', '>', 0)->exists(),
            self::CARD_BALANCE => $account->cards()->where('balance_xof', '>', 0)->exists(),
            self::EPHEMERAL_CARD_BALANCE => EphemeralCard::where('account_id', $account->id)
                ->whereNull('destroyed_at')->where('balance_xof', '>', 0)->exists(),
            self::FOREIGN_BALANCE => $account->foreignBalances()->where('amount_minor_units', '>', 0)->exists(),
            self::PENDING_TRANSACTION => $account->transactions()->where('status', TransactionStatus::Pending)->exists(),
            self::PENDING_INSURANCE => $account->insurancePolicies()->where('status', InsurancePolicyStatus::Pending)->exists(),
        ];

        return array_keys(array_filter($checks));
    }

    public function close(Account $account): void
    {
        DB::transaction(function () use ($account) {
            /** @var Account $account */
            $account = Account::whereKey($account->getKey())->lockForUpdate()->firstOrFail();
            if ($account->isClosed()) {
                return;
            }

            $blockers = $this->blockers($account);
            if ($blockers !== []) {
                throw new AccountClosureBlockedException($blockers);
            }

            $account->tokens()->delete();
            $account->refreshTokens()->delete();
            $account->chatMessages()->delete();
            $account->beneficiaries()->delete();
            $account->billAccounts()->delete();
            EphemeralCard::where('account_id', $account->id)->delete();
            $account->cards()->get()->each->delete();
            $account->pockets()->delete();
            $account->foreignBalances()->delete();

            $account->closed_phone_number = $account->phone_number;
            $account->phone_number = 'closed:'.$account->id;
            $account->pin_hash = null;
            $account->closed_at = now();
            $account->save();
        });
    }

    // Suppression définitive (fin de conservation légale, ou compte de test
    // supprimé par un super_admin) : base et fichiers (pièces KYC,
    // attestations), pour ne jamais laisser de pièce d'identité orpheline.
    public function purge(Account $account): void
    {
        foreach ($account->kycDocuments as $document) {
            $paths = array_filter([$document->front_path, $document->back_path, $document->selfie_path]);
            Storage::disk('kyc')->delete($paths);
            // Dossiers envoyés avant le passage au disque privé `kyc`.
            Storage::disk('public')->delete($paths);
        }
        foreach ($account->insurancePolicies()->whereNotNull('attestation_path')->pluck('attestation_path') as $path) {
            Storage::disk('insurance')->delete($path);
        }

        $account->tokens()->delete();
        $account->delete();
    }
}

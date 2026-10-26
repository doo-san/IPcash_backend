<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Services\Accounts\AccountClosureService;
use Illuminate\Console\Command;

// Fin de la conservation légale : supprime définitivement (base + pièces
// KYC + attestations) les comptes clôturés depuis plus de
// `accounts.closed_retention_years` ans. Planifiée chaque jour.
class PurgeClosedAccounts extends Command
{
    protected $signature = 'accounts:purge-closed';

    protected $description = 'Supprime les comptes clôturés dont la durée de conservation légale est écoulée';

    public function handle(AccountClosureService $closure): int
    {
        $cutoff = now()->subYears((int) config('accounts.closed_retention_years'));
        $purged = 0;

        Account::whereNotNull('closed_at')
            ->where('closed_at', '<', $cutoff)
            ->lazyById()
            ->each(function (Account $account) use ($closure, &$purged) {
                $closure->purge($account);
                $purged++;
            });

        $this->info("{$purged} compte(s) clôturé(s) supprimé(s) définitivement.");

        return self::SUCCESS;
    }
}

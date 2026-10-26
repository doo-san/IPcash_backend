<?php

namespace App\Console\Commands;

use App\Enums\KycVerificationStatus;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

// Test de charge et de cohérence : transferts P2P simultanés entre comptes
// de test, via la vraie API HTTP (serveur en cours d'exécution), puis
// vérification des invariants du registre. Jamais en production. À lancer
// sur un environnement PostgreSQL pour valider les verrous en conditions
// réelles (SQLite sérialise toutes les écritures).
class LoadTestTransfers extends Command
{
    protected $signature = 'loadtest:transfers
        {--url=http://127.0.0.1:8000 : URL du serveur à tester}
        {--accounts=10 : Nombre de comptes de test}
        {--requests=200 : Nombre total de transferts}
        {--concurrency=20 : Requêtes envoyées en même temps}
        {--balance=50000 : Solde initial de chaque compte (XOF)}
        {--keep : Ne pas supprimer les comptes de test à la fin}';

    protected $description = 'Transferts simultanés entre comptes de test puis vérification des soldes (hors production)';

    private const PIN = '482913';

    private const PHONE_PREFIX = '+22179999';

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('Refusé en production.');

            return self::FAILURE;
        }

        $accounts = $this->createAccounts();
        $initialTotal = $accounts->sum('balance_xof');

        try {
            $latencies = $this->fireTransfers($accounts);
            $ok = $this->verify($accounts, $initialTotal);
            $this->report($latencies);
        } finally {
            if (! $this->option('keep')) {
                $this->deleteAccounts($accounts);
            }
        }

        return $ok ? self::SUCCESS : self::FAILURE;
    }

    /** @return Collection<int, array{account: Account, token: string}> */
    private function createAccounts(): Collection
    {
        $this->deleteAccounts(Account::where('phone_number', 'like', self::PHONE_PREFIX.'%')->get()->map(fn ($a) => ['account' => $a]));

        return collect(range(1, (int) $this->option('accounts')))->map(function (int $i) {
            $account = Account::create(['phone_number' => self::PHONE_PREFIX.str_pad((string) $i, 4, '0', STR_PAD_LEFT)]);
            $account->pin_hash = bcrypt(self::PIN);
            $account->kyc_status = KycVerificationStatus::Verified;
            $account->balance_xof = (int) $this->option('balance');
            $account->save();

            return ['account' => $account, 'token' => $account->createToken('loadtest')->plainTextToken, 'balance_xof' => $account->balance_xof];
        });
    }

    /** @return list<float> Latences en ms des requêtes abouties. */
    private function fireTransfers(Collection $accounts): array
    {
        $plan = collect(range(1, (int) $this->option('requests')))->map(function () use ($accounts) {
            [$from, $to] = $accounts->random(2)->values()->all();

            return [
                'token' => $from['token'],
                'key' => (string) Str::uuid(),
                'body' => [
                    'recipientPhoneNumber' => $to['account']->phone_number,
                    'amountXof' => random_int(100, 5000),
                    'pin' => self::PIN,
                ],
            ];
        });
        // Un transfert sur dix est rejoué avec la même clé d'idempotence : il
        // ne doit jamais être exécuté deux fois.
        $plan = $plan->concat($plan->take(intdiv($plan->count(), 10)))->shuffle();

        $statuses = [];
        $latencies = [];
        $bar = $this->output->createProgressBar($plan->count());

        foreach ($plan->chunk((int) $this->option('concurrency')) as $batch) {
            $started = microtime(true);
            $responses = Http::pool(fn (Pool $pool) => $batch->map(
                fn (array $call) => $pool->baseUrl($this->option('url'))
                    ->withToken($call['token'])
                    ->withHeaders(['Idempotency-Key' => $call['key']])
                    ->acceptJson()
                    ->timeout(30)
                    ->post('/api/transfers/p2p', $call['body']),
            )->all());
            $elapsed = (microtime(true) - $started) * 1000;

            foreach ($responses as $response) {
                $status = $response instanceof \Throwable ? 'erreur réseau' : (string) $response->status();
                $statuses[$status] = ($statuses[$status] ?? 0) + 1;
                $latencies[] = $elapsed;
            }
            $bar->advance($batch->count());
        }
        $bar->finish();
        $this->newLine(2);

        ksort($statuses);
        $this->table(['Statut HTTP', 'Requêtes'], collect($statuses)->map(fn ($n, $s) => [$s, $n])->values()->all());

        return $latencies;
    }

    private function verify(Collection $accounts, int $initialTotal): bool
    {
        $ids = $accounts->pluck('account.id');
        $balances = Account::whereIn('id', $ids)->pluck('balance_xof');
        $outgoing = Transaction::whereIn('account_id', $ids)
            ->where('type', TransactionType::TransferOut)
            ->where('status', TransactionStatus::Completed)
            ->get();
        $incomingReferences = Transaction::whereIn('account_id', $ids)
            ->where('type', TransactionType::TransferIn)
            ->pluck('reference');

        $fees = (int) $outgoing->sum('fee_xof');
        $expectedTotal = $initialTotal - $fees;
        $orphans = $outgoing->reject(fn ($tx) => $incomingReferences->contains("{$tx->reference}-R"))->count();

        $checks = [
            ['Argent total = initial - frais', $balances->sum() === $expectedTotal, "{$balances->sum()} / attendu {$expectedTotal}"],
            ['Aucun solde négatif', $balances->min() >= 0, 'minimum '.$balances->min()],
            ['Chaque débit a son crédit', $orphans === 0, "{$orphans} débit(s) sans crédit"],
            ['Transferts exécutés', $outgoing->count() > 0, $outgoing->count().' transfert(s)'],
        ];
        $this->table(['Vérification', 'Résultat', 'Détail'], array_map(
            fn ($c) => [$c[0], $c[1] ? 'OK' : 'ÉCHEC', $c[2]],
            $checks,
        ));

        return collect($checks)->every(fn ($c) => $c[1]);
    }

    /** @param list<float> $latencies */
    private function report(array $latencies): void
    {
        if ($latencies === []) {
            return;
        }
        sort($latencies);
        $pick = fn (float $p) => (int) round($latencies[(int) floor((count($latencies) - 1) * $p)]);
        $this->line("Durée par lot de requêtes simultanées : médiane {$pick(0.5)} ms, p95 {$pick(0.95)} ms, max {$pick(1.0)} ms");
    }

    private function deleteAccounts(Collection $accounts): void
    {
        $accounts->each(function (array $entry) {
            $entry['account']->tokens()->delete();
            $entry['account']->transactions()->delete();
            $entry['account']->delete();
        });
    }
}

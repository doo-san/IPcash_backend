<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\CashioController;
use App\Models\MobileMoneyProvider;
use App\Models\Transaction;
use App\Services\Wave\WaveClient;
use App\Services\Wave\WaveException;
use Illuminate\Console\Command;

// Relance auprès de Wave les retraits (`cashOut`) restés `pending` avec un
// `provider_reference` (un payout `processing`, voir
// `CashioController::cashoutViaWave`) — nécessaire car l'API Payout de Wave
// n'a pas de webhook documenté pour ce cas, contrairement à Checkout.
class FinalizeWavePayouts extends Command
{
    protected $signature = 'wave:finalize-payouts';

    protected $description = 'Interroge Wave pour finaliser les retraits payout restés "processing"';

    public function handle(): int
    {
        $provider = MobileMoneyProvider::query()->get()->first(fn (MobileMoneyProvider $p) => $p->isWave());
        if (! $provider?->isConfigured()) {
            return self::SUCCESS;
        }

        $client = new WaveClient($provider);
        $controller = new CashioController;

        Transaction::query()
            ->where('type', 'cashOut')
            ->where('status', 'pending')
            ->whereNotNull('provider_reference')
            ->each(function (Transaction $transaction) use ($client, $controller) {
                try {
                    $status = $client->getPayoutStatus($transaction->provider_reference);
                } catch (WaveException $e) {
                    $this->warn("Payout {$transaction->provider_reference} : {$e->getMessage()}");

                    return;
                }

                $controller->applyWavePayoutStatus(
                    $transaction,
                    $transaction->account,
                    -$transaction->amount_xof,
                    null,
                    $status,
                    $transaction->provider_reference,
                );
            });

        return self::SUCCESS;
    }
}

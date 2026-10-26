<?php

namespace App\Services;

use App\Models\Account;
use Illuminate\Support\Facades\Cache;

// Plafonds quotidiens d'appels Anthropic (coût) : par client et pour toute
// l'app. Compteurs remis à zéro chaque jour à minuit. Incrémenter puis
// comparer (et non l'inverse) garde le plafond exact sous requêtes
// simultanées ; un client déjà au plafond ne consomme pas le global.
class AiSupportBudget
{
    public function tryConsume(Account $account): bool
    {
        $day = now()->format('Y-m-d');

        if ($this->hit("ai-support:{$day}:account:{$account->id}") > $this->perAccountLimit()) {
            return false;
        }

        return $this->hit("ai-support:{$day}:global") <= $this->globalLimit();
    }

    private function hit(string $key): int
    {
        Cache::add($key, 0, now()->endOfDay());

        return (int) Cache::increment($key);
    }

    private function perAccountLimit(): int
    {
        return (int) config('services.anthropic.daily_limit_per_account');
    }

    private function globalLimit(): int
    {
        return (int) config('services.anthropic.daily_limit_global');
    }
}

<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;

// Partagé par tous les contrôleurs qui créent une `Transaction` derrière
// un en-tête `Idempotency-Key` (CLAUDE.md règle 2) : rejouer la même clé
// avec les mêmes paramètres renvoie la transaction déjà créée, jamais une
// deuxième ; la rejouer avec des paramètres différents est un conflit.
trait HandlesIdempotency
{
    /**
     * @param  array<string, mixed>  $matchAttributes  Colonnes à comparer
     *                                                 pour détecter un rejeu incohérent.
     * @return Transaction|JsonResponse|null Transaction existante à
     *                                       renvoyer telle quelle, réponse 409 en cas de conflit, ou null si
     *                                       c'est une nouvelle opération.
     */
    protected function findIdempotentTransaction(Account $account, string $idempotencyKey, array $matchAttributes): Transaction|JsonResponse|null
    {
        $existing = $account->transactions()->where('idempotency_key', $idempotencyKey)->first();

        if (! $existing) {
            return null;
        }

        foreach ($matchAttributes as $attribute => $value) {
            if ($existing->{$attribute} !== $value) {
                return response()->json([
                    'code' => 'IDEMPOTENCY_CONFLICT',
                    'message' => 'Cette clé d\'idempotence a déjà été utilisée avec des paramètres différents.',
                ], 409);
            }
        }

        return $existing;
    }
}

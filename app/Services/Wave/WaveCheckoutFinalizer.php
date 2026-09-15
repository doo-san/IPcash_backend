<?php

namespace App\Services\Wave;

use App\Models\Transaction;

// Finalise une Transaction cashIn Wave à partir d'un `payment_status` Wave
// (`succeeded`, `cancelled`, ou autre chose auquel cas on ne touche à
// rien) — partagé entre WaveWebhookController (notification poussée) et
// le filet de sécurité posé sur /wave/return (vérification active via
// WaveClient::getCheckoutSessionStatus, voir routes/web.php), pour ne pas
// dupliquer la logique de crédit dans les deux.
class WaveCheckoutFinalizer
{
    public function finalize(Transaction $transaction, string $paymentStatus, ?string $failureReason = null): void
    {
        if ($transaction->status->value !== 'pending') {
            return;
        }

        if ($paymentStatus === 'succeeded') {
            $transaction->account()->increment('balance_xof', $transaction->amount_xof);
            $transaction->update(['status' => 'completed']);
        } elseif ($paymentStatus === 'cancelled') {
            $transaction->update([
                'status' => 'failed',
                'failure_reason' => $failureReason ?? 'Paiement Wave non abouti.',
            ]);
        }
        // Autre statut (`processing`...) : on laisse `pending`.
    }
}

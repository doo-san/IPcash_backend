<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

// Reçoit la notification asynchrone d'OM Pay (callbackUrl passée à
// `OrangeMoneyClient::preparePayment`). Le format exact du payload n'est
// pas documenté dans le Swagger fourni (seul `/v1/onlinePayment/prepare`
// y figure) — on logue toujours le corps brut pour ajuster l'extraction
// dès le premier vrai appel reçu, et on ne fait jamais évoluer une
// transaction vers `completed` sans avoir positivement identifié son statut
// (CLAUDE.md règle 3 : en cas de doute, elle reste `pending`).
class OrangeMoneyWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        Log::info('[orange-money] webhook reçu', $request->all());

        $reference = $request->input('reference') ?? $request->input('order_id') ?? $request->input('orderId');
        $status = $request->input('status') ?? $request->input('txnstatus');

        if (! is_string($reference)) {
            return response()->json(['received' => true]);
        }

        $transaction = Transaction::where('reference', $reference)->first();
        if (! $transaction || $transaction->status->value !== 'pending') {
            return response()->json(['received' => true]);
        }

        $normalizedStatus = is_string($status) ? strtolower($status) : null;

        if (in_array($normalizedStatus, ['success', 'successful', 'completed', 'ok'], true)) {
            $transaction->account()->increment('balance_xof', $transaction->amount_xof);
            $transaction->update(['status' => 'completed']);
        } elseif (in_array($normalizedStatus, ['failed', 'failure', 'cancelled', 'canceled', 'expired'], true)) {
            $transaction->update([
                'status' => 'failed',
                'failure_reason' => 'Paiement Orange Money non abouti.',
            ]);
        }
        // Statut ni reconnu comme succès ni comme échec : on laisse `pending`
        // (rien à faire) plutôt que de deviner.

        return response()->json(['received' => true]);
    }
}

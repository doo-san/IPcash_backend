<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Implémente le tag `transactions` de openapi.yaml. Pagination par
// curseur : les identifiants sont des UUID ordonnés (`HasUuids` trie déjà
// chronologiquement), donc `id` sert directement de curseur — pas besoin
// d'encoder un curseur séparé.
class TransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var Account $account */
        $account = $request->user();
        $limit = min((int) $request->query('limit', 20), 100);

        $query = $account->transactions()->orderByDesc('id');

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($from = $request->query('from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->query('to')) {
            $query->whereDate('created_at', '<=', $to);
        }
        if ($cursor = $request->query('cursor')) {
            $query->where('id', '<', $cursor);
        }

        $items = $query->limit($limit + 1)->get();
        $hasMore = $items->count() > $limit;
        $items = $items->take($limit);

        return response()->json([
            'items' => TransactionResource::collection($items)->resolve(),
            'nextCursor' => $hasMore ? $items->last()->id : null,
        ]);
    }

    public function show(Request $request, string $id): TransactionResource|Response
    {
        /** @var Account $account */
        $account = $request->user();
        $transaction = $account->transactions()->find($id);

        if (! $transaction) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Transaction introuvable.',
            ], 404);
        }

        return new TransactionResource($transaction);
    }
}

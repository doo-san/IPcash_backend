<?php

namespace App\Http\Controllers\Api;

use App\Enums\TransactionType;
use App\Http\Controllers\Api\Concerns\HandlesIdempotency;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cashio\AmountOnlyRequest;
use App\Http\Requests\Pocket\CreatePocketRequest;
use App\Http\Requests\Pocket\LockPocketRequest;
use App\Http\Resources\PocketResource;
use App\Http\Resources\TransactionResource;
use App\Models\Account;
use App\Models\Pocket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

// Implémente le tag `pockets` de openapi.yaml (poches d'épargne).
// Opération purement interne au solde IPCash (CLAUDE.md règle 9) : un
// virement vers/depuis une poche peut simuler une réussite réaliste, à la
// différence des paiements marchand/facture/crédit/eSIM/assurance.
class PocketController extends Controller
{
    use HandlesIdempotency;

    public function index(Request $request): AnonymousResourceCollection
    {
        return PocketResource::collection($request->user()->pockets);
    }

    public function store(CreatePocketRequest $request): JsonResponse
    {
        /** @var Account $account */
        $account = $request->user();
        $pocket = $account->pockets()->create(['name' => $request->validated('name')]);

        return response()->json((new PocketResource($pocket))->resolve(), 201);
    }

    public function update(CreatePocketRequest $request, string $id): PocketResource|Response
    {
        $pocket = $this->findOwnedPocket($request, $id);
        if (! $pocket) {
            return $this->notFound();
        }

        $pocket->name = $request->validated('name');
        $pocket->save();

        return new PocketResource($pocket);
    }

    public function destroy(Request $request, string $id): Response
    {
        $pocket = $this->findOwnedPocket($request, $id);
        if (! $pocket) {
            return $this->notFound();
        }

        if ($pocket->balance_xof !== 0) {
            return response()->json([
                'code' => 'POCKET_NOT_EMPTY',
                'message' => 'La poche doit être vidée avant d\'être supprimée.',
            ], 400);
        }

        $pocket->delete();

        return response()->noContent();
    }

    public function transferIn(AmountOnlyRequest $request, string $id): JsonResponse|Response
    {
        /** @var Account $account */
        $account = $request->user();
        $pocket = $this->findOwnedPocket($request, $id);
        if (! $pocket) {
            return $this->notFound();
        }

        $amountXof = (int) $request->validated('amountXof');
        $idempotencyKey = $request->header('Idempotency-Key');

        $existing = $this->findIdempotentTransaction($account, $idempotencyKey, ['pocket_id' => $pocket->id, 'amount_xof' => $amountXof]);
        if ($existing instanceof JsonResponse) {
            return $existing;
        }
        if ($existing) {
            return response()->json((new TransactionResource($existing))->resolve(), 202);
        }

        if ($account->balance_xof < $amountXof) {
            return response()->json(['code' => 'INSUFFICIENT_FUNDS', 'message' => 'Solde principal insuffisant.'], 402);
        }

        $transaction = DB::transaction(function () use ($account, $pocket, $amountXof, $idempotencyKey) {
            $account->decrement('balance_xof', $amountXof);
            $pocket->increment('balance_xof', $amountXof);

            return $account->transactions()->create([
                'pocket_id' => $pocket->id,
                'type' => TransactionType::PocketTransferIn,
                'status' => 'completed',
                'amount_xof' => $amountXof,
                'reference' => 'IP-'.strtoupper(Str::random(8)),
                'idempotency_key' => $idempotencyKey,
                'main_balance_after_xof' => $account->balance_xof,
                'pocket_balance_after_xof' => $pocket->balance_xof,
                'pocket_name' => $pocket->name,
            ]);
        });

        return response()->json((new TransactionResource($transaction))->resolve(), 202);
    }

    public function transferOut(AmountOnlyRequest $request, string $id): JsonResponse|Response
    {
        /** @var Account $account */
        $account = $request->user();
        $pocket = $this->findOwnedPocket($request, $id);
        if (! $pocket) {
            return $this->notFound();
        }

        $amountXof = (int) $request->validated('amountXof');
        $idempotencyKey = $request->header('Idempotency-Key');

        $existing = $this->findIdempotentTransaction($account, $idempotencyKey, ['pocket_id' => $pocket->id, 'amount_xof' => -$amountXof]);
        if ($existing instanceof JsonResponse) {
            return $existing;
        }
        if ($existing) {
            return response()->json((new TransactionResource($existing))->resolve(), 202);
        }

        if ($pocket->isLocked()) {
            return response()->json(['code' => 'POCKET_LOCKED', 'message' => 'Cette poche est verrouillée.'], 400);
        }

        if ($pocket->balance_xof < $amountXof) {
            return response()->json(['code' => 'INSUFFICIENT_FUNDS', 'message' => 'Solde de la poche insuffisant.'], 402);
        }

        $transaction = DB::transaction(function () use ($account, $pocket, $amountXof, $idempotencyKey) {
            $pocket->decrement('balance_xof', $amountXof);
            $account->increment('balance_xof', $amountXof);

            return $account->transactions()->create([
                'pocket_id' => $pocket->id,
                'type' => TransactionType::PocketTransferOut,
                'status' => 'completed',
                'amount_xof' => -$amountXof,
                'reference' => 'IP-'.strtoupper(Str::random(8)),
                'idempotency_key' => $idempotencyKey,
                'main_balance_after_xof' => $account->balance_xof,
                'pocket_balance_after_xof' => $pocket->balance_xof,
                'pocket_name' => $pocket->name,
            ]);
        });

        return response()->json((new TransactionResource($transaction))->resolve(), 202);
    }

    public function lock(LockPocketRequest $request, string $id): PocketResource|Response
    {
        $pocket = $this->findOwnedPocket($request, $id);
        if (! $pocket) {
            return $this->notFound();
        }

        $pocket->locked_until = $request->validated('until');
        $pocket->save();

        return new PocketResource($pocket);
    }

    public function unlock(Request $request, string $id): PocketResource|Response
    {
        $pocket = $this->findOwnedPocket($request, $id);
        if (! $pocket) {
            return $this->notFound();
        }

        $pocket->locked_until = null;
        $pocket->save();

        return new PocketResource($pocket);
    }

    public function transactions(Request $request, string $id): AnonymousResourceCollection|Response
    {
        $pocket = $this->findOwnedPocket($request, $id);
        if (! $pocket) {
            return $this->notFound();
        }

        return TransactionResource::collection($pocket->transactions()->latest()->get());
    }

    private function findOwnedPocket(Request $request, string $id): ?Pocket
    {
        /** @var Account $account */
        $account = $request->user();

        return $account->pockets()->find($id);
    }

    private function notFound(): Response
    {
        return response()->json(['code' => 'NOT_FOUND', 'message' => 'Poche introuvable.'], 404);
    }
}

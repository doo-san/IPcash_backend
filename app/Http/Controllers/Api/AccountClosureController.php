<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Services\Accounts\AccountClosureBlockedException;
use App\Services\Accounts\AccountClosureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

// Clôture du compte à la demande du client (exigence App Store : la
// suppression doit pouvoir se lancer depuis l'app). Voir
// AccountClosureService pour ce qui est effacé ou conservé.
class AccountClosureController extends Controller
{
    public function __construct(private readonly AccountClosureService $closure) {}

    public function eligibility(Request $request): JsonResponse
    {
        /** @var Account $account */
        $account = $request->user();
        $blockers = $this->closure->blockers($account);

        return response()->json([
            'eligible' => $blockers === [],
            'blockers' => $blockers,
            'retentionYears' => (int) config('accounts.closed_retention_years'),
        ]);
    }

    public function close(Request $request): JsonResponse
    {
        $data = $request->validate(['pin' => ['required', 'string', 'regex:/^[0-9]{6}$/']]);
        /** @var Account $account */
        $account = $request->user();

        if ($account->pin_hash === null || ! Hash::check($data['pin'], $account->pin_hash)) {
            return response()->json(['code' => 'UNAUTHORIZED', 'message' => 'Code secret invalide.'], 401);
        }

        try {
            $this->closure->close($account);
        } catch (AccountClosureBlockedException $e) {
            return response()->json([
                'code' => 'ACCOUNT_CLOSURE_BLOCKED',
                'message' => 'Le compte ne peut pas encore être clôturé.',
                'details' => ['blockers' => $e->blockers],
            ], 409);
        }

        return response()->json(null, 204);
    }
}

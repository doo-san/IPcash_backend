<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AccountResource;
use App\Models\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// Implémente le tag `accounts` de openapi.yaml.
class AccountController extends Controller
{
    public function me(Request $request): AccountResource
    {
        return new AccountResource($request->user());
    }

    public function balance(Request $request): JsonResponse
    {
        /** @var Account $account */
        $account = $request->user();

        return response()->json([
            'balanceXof' => $account->balance_xof,
            'currency' => 'XOF',
            'updatedAt' => $account->updated_at->toIso8601String(),
        ]);
    }
}

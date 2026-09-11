<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\ProfileResource;
use App\Http\Resources\SessionResource;
use App\Models\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

// Implémente le tag `profile` de openapi.yaml.
class ProfileController extends Controller
{
    public function show(Request $request): ProfileResource
    {
        return new ProfileResource($request->user());
    }

    // Appareils actuellement connectés au compte = les `RefreshToken`
    // encore valides (un par appareil). `deviceId` en query permet à l'app
    // de marquer sa propre session `isCurrent` (voir SessionResource).
    public function sessions(Request $request): AnonymousResourceCollection
    {
        /** @var Account $account */
        $account = $request->user();

        $sessions = $account->refreshTokens()
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->get();

        return SessionResource::collection($sessions);
    }

    // Déconnecte un autre appareil : révoque son `RefreshToken` (il ne
    // pourra plus renouveler, et tombe au plus tard à l'expiration du
    // token d'accès en cours, 15 min — même comportement que la
    // révocation depuis l'admin).
    public function revokeSession(Request $request, string $id): JsonResponse
    {
        /** @var Account $account */
        $account = $request->user();

        $session = $account->refreshTokens()->whereNull('revoked_at')->find($id);
        if (! $session) {
            return response()->json(['code' => 'NOT_FOUND', 'message' => 'Session introuvable.'], 404);
        }

        $session->update(['revoked_at' => now()]);

        return response()->json(null, 204);
    }

    public function update(UpdateProfileRequest $request): ProfileResource
    {
        /** @var Account $account */
        $account = $request->user();
        $data = $request->validated();

        if (array_key_exists('fullName', $data)) {
            // `Account` distingue firstName/lastName — on éclate sur le
            // premier espace, seule source de vérité côté serveur pour ce
            // champ combiné.
            $parts = explode(' ', trim($data['fullName']), 2);
            $account->first_name = $parts[0] !== '' ? $parts[0] : null;
            $account->last_name = $parts[1] ?? null;
        }
        if (array_key_exists('email', $data)) {
            $account->email = $data['email'];
        }
        if (array_key_exists('notificationsEnabled', $data)) {
            $account->notifications_enabled = $data['notificationsEnabled'];
        }
        if (array_key_exists('preferredLocale', $data)) {
            $account->preferred_locale = $data['preferredLocale'];
        }
        $account->save();

        return new ProfileResource($account);
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Miroir du schéma `Session` d'openapi.yaml — une session active = un
// `RefreshToken` valide (un par appareil, `device_id` stable même après
// rotation, voir `AuthController::refresh`). `isCurrent` : le `deviceId`
// passé par l'app correspond à celui de la session.
class SessionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $currentDeviceId = $request->query('deviceId');

        return [
            'id' => $this->id,
            'deviceLabel' => $this->device_id ?? 'Appareil inconnu',
            'lastActiveAt' => $this->created_at->toIso8601String(),
            'isCurrent' => $currentDeviceId !== null
                && $this->device_id === $currentDeviceId,
        ];
    }
}

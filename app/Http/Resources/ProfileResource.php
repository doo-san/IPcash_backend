<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Miroir du schéma `Profile` d'openapi.yaml — `fullName` unique ici (à la
// différence d'`Account` qui a firstName/lastName séparés), dérivé des
// mêmes colonnes.
class ProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'phoneNumber' => $this->phone_number,
            'fullName' => $this->first_name !== null || $this->last_name !== null
                ? trim("{$this->first_name} {$this->last_name}")
                : null,
            'email' => $this->email,
            'notificationsEnabled' => $this->notifications_enabled,
            'preferredLocale' => $this->preferred_locale,
            'biometricEnabled' => $this->biometric_enabled,
        ];
    }
}

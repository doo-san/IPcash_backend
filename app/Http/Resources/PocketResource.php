<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Miroir du schéma `Pocket` d'openapi.yaml (poche d'épargne).
class PocketResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'balanceXof' => $this->balance_xof,
            'lockedUntil' => $this->locked_until?->toIso8601String(),
        ];
    }
}

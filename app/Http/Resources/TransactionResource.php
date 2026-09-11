<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Miroir du schéma `Transaction` d'openapi.yaml.
class TransactionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'status' => $this->status->value,
            'amountXof' => $this->amount_xof,
            'feeXof' => $this->fee_xof,
            'counterpartyName' => $this->counterparty_name,
            'counterpartyPhoneNumber' => $this->counterparty_phone_number,
            'note' => $this->note,
            'reference' => $this->reference,
            'createdAt' => $this->created_at->toIso8601String(),
            'failureReason' => $this->failure_reason,
            'foreignCurrencyCode' => $this->foreign_currency_code,
            'foreignBalanceAfterMinorUnits' => $this->foreign_balance_after_minor_units,
            'mainBalanceAfterXof' => $this->main_balance_after_xof,
            'pocketBalanceAfterXof' => $this->pocket_balance_after_xof,
            'pocketName' => $this->pocket_name,
            'cardBalanceAfterXof' => $this->card_balance_after_xof,
            'cardLast4' => $this->card_last4,
        ];
    }
}

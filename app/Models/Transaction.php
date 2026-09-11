<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Concerns\LogsAdminActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasUuids, LogsAdminActivity;

    protected $attributes = [
        'status' => 'pending',
        'fee_xof' => 0,
    ];

    protected $fillable = [
        'account_id',
        'pocket_id',
        'card_id',
        'type',
        'status',
        'amount_xof',
        'fee_xof',
        'counterparty_name',
        'counterparty_phone_number',
        'note',
        'reference',
        'idempotency_key',
        'failure_reason',
        'foreign_currency_code',
        'foreign_balance_after_minor_units',
        'main_balance_after_xof',
        'pocket_balance_after_xof',
        'pocket_name',
        'card_balance_after_xof',
        'card_last4',
    ];

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'status' => TransactionStatus::class,
            'amount_xof' => 'integer',
            'fee_xof' => 'integer',
            'foreign_balance_after_minor_units' => 'integer',
            'main_balance_after_xof' => 'integer',
            'pocket_balance_after_xof' => 'integer',
            'card_balance_after_xof' => 'integer',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function pocket(): BelongsTo
    {
        return $this->belongsTo(Pocket::class);
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }
}

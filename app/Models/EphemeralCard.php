<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EphemeralCard extends Model
{
    use HasUuids;

    protected $attributes = ['balance_xof' => 0];

    protected $fillable = [
        'account_id',
        'number',
        'cvv',
        'expiry_month',
        'expiry_year',
        'balance_xof',
        'destroyed_at',
    ];

    // Chiffrés au repos malgré l'invalidité Luhn volontaire (règle 9 de
    // CLAUDE.md) : ce sont quand même des données à forme carte bancaire.
    protected function casts(): array
    {
        return [
            'number' => 'encrypted',
            'cvv' => 'encrypted',
            'balance_xof' => 'integer',
            'destroyed_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}

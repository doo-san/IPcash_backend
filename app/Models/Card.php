<?php

namespace App\Models;

use App\Enums\CardStatus;
use App\Models\Concerns\LogsAdminActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Card extends Model
{
    use HasUuids, LogsAdminActivity;

    // 200 000 / 1 500 000 : mêmes défauts que `card_fake_data_source.dart`
    // côté Flutter — un vrai réglage produit devrait plutôt vivre dans
    // `CardProductDefaultsResource`, voir ce fichier pour la valeur
    // effective utilisée à l'émission d'une nouvelle carte.
    protected $attributes = [
        'status' => 'active',
        'balance_xof' => 0,
    ];

    protected $fillable = [
        'account_id',
        'last4',
        'status',
        'balance_xof',
        'expiry_month',
        'expiry_year',
        'daily_limit_xof',
        'monthly_limit_xof',
        'external_processor_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => CardStatus::class,
            'balance_xof' => 'integer',
            'daily_limit_xof' => 'integer',
            'monthly_limit_xof' => 'integer',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function revealTokens(): HasMany
    {
        return $this->hasMany(CardRevealToken::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}

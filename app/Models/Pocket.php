<?php

namespace App\Models;

use App\Models\Concerns\LogsAdminActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pocket extends Model
{
    use HasUuids, LogsAdminActivity;

    protected $attributes = ['balance_xof' => 0];

    protected $fillable = ['account_id', 'name', 'balance_xof', 'locked_until'];

    protected function casts(): array
    {
        return [
            'balance_xof' => 'integer',
            'locked_until' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function isLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }
}

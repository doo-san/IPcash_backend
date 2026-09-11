<?php

namespace App\Models;

use App\Models\Concerns\LogsAdminActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ForeignBalance extends Model
{
    use LogsAdminActivity;

    protected $fillable = ['account_id', 'currency_code', 'amount_minor_units'];

    protected function casts(): array
    {
        return ['amount_minor_units' => 'integer'];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}

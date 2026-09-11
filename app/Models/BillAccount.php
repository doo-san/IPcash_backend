<?php

namespace App\Models;

use App\Enums\BillProviderType;
use App\Models\Concerns\LogsAdminActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillAccount extends Model
{
    use HasUuids, LogsAdminActivity;

    protected $fillable = ['account_id', 'bill_provider_type', 'nickname', 'account_number'];

    protected function casts(): array
    {
        return ['bill_provider_type' => BillProviderType::class];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}

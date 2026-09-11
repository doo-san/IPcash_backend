<?php

namespace App\Models;

use App\Models\Concerns\LogsAdminActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Beneficiary extends Model
{
    use HasUuids, LogsAdminActivity;

    protected $fillable = ['account_id', 'name', 'phone_number'];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}

<?php

namespace App\Models;

use App\Enums\MerchantIdentifierType;
use App\Models\Concerns\LogsAdminActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Merchant extends Model
{
    use HasUuids, LogsAdminActivity;

    protected $fillable = ['name', 'identifier_type', 'identifier_value'];

    protected function casts(): array
    {
        return ['identifier_type' => MerchantIdentifierType::class];
    }
}

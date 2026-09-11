<?php

namespace App\Models;

use App\Models\Concerns\LogsAdminActivity;
use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    use LogsAdminActivity;

    protected $primaryKey = 'currency_code';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $attributes = ['is_pegged_to_xof' => false];

    protected $fillable = [
        'currency_code',
        'name',
        'flag',
        'rate_to_xof',
        'is_pegged_to_xof',
    ];

    protected function casts(): array
    {
        return [
            'rate_to_xof' => 'decimal:4',
            'is_pegged_to_xof' => 'boolean',
        ];
    }
}

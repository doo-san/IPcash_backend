<?php

namespace App\Models;

use App\Models\Concerns\LogsAdminActivity;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use LogsAdminActivity;

    protected $primaryKey = 'dial_code';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $attributes = ['is_active' => true];

    protected $fillable = [
        'dial_code',
        'name',
        'flag',
        'min_digits',
        'max_digits',
        'mobile_prefixes',
        'currency_code',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'min_digits' => 'integer',
            'max_digits' => 'integer',
            'mobile_prefixes' => 'array',
            'is_active' => 'boolean',
        ];
    }
}

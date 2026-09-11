<?php

namespace App\Models;

use App\Enums\EsimScope;
use App\Models\Concerns\LogsAdminActivity;
use Illuminate\Database\Eloquent\Model;

class EsimPlan extends Model
{
    use LogsAdminActivity;

    protected $attributes = ['is_active' => true];

    protected $fillable = [
        'scope',
        'data_gb',
        'validity_days',
        'price_xof',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'scope' => EsimScope::class,
            'data_gb' => 'integer',
            'validity_days' => 'integer',
            'price_xof' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}

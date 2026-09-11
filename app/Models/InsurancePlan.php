<?php

namespace App\Models;

use App\Enums\InsuranceType;
use App\Models\Concerns\LogsAdminActivity;
use Illuminate\Database\Eloquent\Model;

class InsurancePlan extends Model
{
    use LogsAdminActivity;

    protected $attributes = ['is_active' => true];

    protected $fillable = [
        'insurance_type',
        'duration_months',
        'price_xof',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'insurance_type' => InsuranceType::class,
            'duration_months' => 'integer',
            'price_xof' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}

<?php

namespace App\Models;

use App\Enums\BillProviderType;
use App\Models\Concerns\LogsAdminActivity;
use Illuminate\Database\Eloquent\Model;

class BillProviderPlan extends Model
{
    use LogsAdminActivity;

    protected $attributes = ['is_active' => true];

    protected $fillable = ['bill_provider_type', 'kind', 'code', 'label', 'price_xof', 'is_active'];

    protected function casts(): array
    {
        return [
            'bill_provider_type' => BillProviderType::class,
            'price_xof' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}

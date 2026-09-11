<?php

namespace App\Models;

use App\Enums\FinancialInstitutionType;
use App\Models\Concerns\HasLogo;
use App\Models\Concerns\LogsAdminActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FinancialInstitution extends Model
{
    use HasLogo, HasUuids, LogsAdminActivity;

    protected $attributes = ['is_active' => true];

    protected $fillable = ['name', 'type', 'logo_url', 'is_active'];

    protected function casts(): array
    {
        return [
            'type' => FinancialInstitutionType::class,
            'is_active' => 'boolean',
        ];
    }
}

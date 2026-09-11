<?php

namespace App\Models;

use App\Models\Concerns\HasLogo;
use App\Models\Concerns\LogsAdminActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CreditOperator extends Model
{
    use HasLogo, HasUuids, LogsAdminActivity;

    protected $attributes = ['is_available' => true, 'is_live' => false];

    protected $fillable = [
        'name',
        'logo_url',
        'is_available',
        'api_base_url',
        'api_key',
        'api_secret',
        'is_live',
    ];

    protected $hidden = ['api_key', 'api_secret'];

    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
            'api_key' => 'encrypted',
            'api_secret' => 'encrypted',
            'is_live' => 'boolean',
        ];
    }

    public function isConfigured(): bool
    {
        return $this->api_key !== null;
    }
}

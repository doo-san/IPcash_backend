<?php

namespace App\Models;

use App\Models\Concerns\HasLogo;
use App\Models\Concerns\LogsAdminActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillProvider extends Model
{
    use HasLogo, LogsAdminActivity;

    protected $primaryKey = 'type';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $attributes = ['is_active' => true, 'is_live' => false];

    protected $fillable = [
        'type',
        'name',
        'logo_url',
        'is_active',
        'api_base_url',
        'api_key',
        'api_secret',
        'is_live',
    ];

    protected $hidden = ['api_key', 'api_secret'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'api_key' => 'encrypted',
            'api_secret' => 'encrypted',
            'is_live' => 'boolean',
        ];
    }

    public function isConfigured(): bool
    {
        return $this->api_key !== null;
    }

    // Clé primaire = `type` (voir plus haut) : la FK côté `bill_provider_plans`
    // porte le même nom (`bill_provider_type`) plutôt que le traditionnel
    // `bill_provider_id`, donc précisée explicitement ici.
    public function plans(): HasMany
    {
        return $this->hasMany(BillProviderPlan::class, 'bill_provider_type', 'type');
    }
}

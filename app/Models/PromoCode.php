<?php

namespace App\Models;

use App\Models\Concerns\LogsAdminActivity;
use Illuminate\Database\Eloquent\Model;

class PromoCode extends Model
{
    use LogsAdminActivity;

    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $attributes = ['redemptions_count' => 0, 'is_active' => true];

    protected $fillable = [
        'code',
        'discount_type',
        'discount_value',
        'max_redemptions',
        'redemptions_count',
        'valid_from',
        'valid_until',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'integer',
            'max_redemptions' => 'integer',
            'redemptions_count' => 'integer',
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function isValidNow(): bool
    {
        if (! $this->is_active) {
            return false;
        }
        if ($this->valid_from !== null && $this->valid_from->isFuture()) {
            return false;
        }
        if ($this->valid_until !== null && $this->valid_until->isPast()) {
            return false;
        }

        return $this->max_redemptions === null || $this->redemptions_count < $this->max_redemptions;
    }
}

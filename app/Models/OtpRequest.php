<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class OtpRequest extends Model
{
    use HasUuids;

    protected $attributes = ['attempts' => 0, 'locale' => 'fr'];

    protected $fillable = [
        'phone_number',
        'code_hash',
        'locale',
        'attempts',
        'expires_at',
        'verified_at',
        'session_token',
        'session_token_expires_at',
    ];

    protected $hidden = ['code_hash', 'session_token'];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
            'session_token_expires_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}

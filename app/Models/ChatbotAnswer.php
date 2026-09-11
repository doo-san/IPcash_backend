<?php

namespace App\Models;

use App\Models\Concerns\LogsAdminActivity;
use Illuminate\Database\Eloquent\Model;

class ChatbotAnswer extends Model
{
    use LogsAdminActivity;

    protected $attributes = ['locale' => 'fr', 'is_fallback' => false, 'is_active' => true];

    protected $fillable = ['locale', 'keywords', 'answer', 'is_fallback', 'is_active'];

    protected function casts(): array
    {
        return [
            'keywords' => 'array',
            'is_fallback' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}

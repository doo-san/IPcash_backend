<?php

namespace App\Models;

use App\Models\Concerns\LogsAdminActivity;
use Illuminate\Database\Eloquent\Model;

class FaqEntry extends Model
{
    use LogsAdminActivity;

    protected $attributes = ['locale' => 'fr', 'position' => 0, 'is_published' => true];

    protected $fillable = ['locale', 'question', 'answer', 'position', 'is_published'];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_published' => 'boolean',
        ];
    }
}

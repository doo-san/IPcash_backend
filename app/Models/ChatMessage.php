<?php

namespace App\Models;

use App\Enums\ChatRole;
use App\Models\Concerns\LogsAdminActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    use HasUuids, LogsAdminActivity;

    protected $fillable = ['account_id', 'role', 'text'];

    protected function casts(): array
    {
        return ['role' => ChatRole::class];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}

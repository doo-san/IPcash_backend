<?php

namespace App\Models;

use App\Enums\KycDocumentType;
use App\Enums\KycVerificationStatus;
use App\Models\Concerns\LogsAdminActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KycDocument extends Model
{
    use HasUuids, LogsAdminActivity;

    protected $attributes = [
        'status' => 'pending',
        'has_client_side_anomaly' => false,
    ];

    protected $fillable = [
        'account_id',
        'document_type',
        'front_path',
        'back_path',
        'selfie_path',
        'has_client_side_anomaly',
        'document_analysis',
        'face_verification',
        'status',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'document_type' => KycDocumentType::class,
            'status' => KycVerificationStatus::class,
            'has_client_side_anomaly' => 'boolean',
            'document_analysis' => 'array',
            'face_verification' => 'array',
            'reviewed_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}

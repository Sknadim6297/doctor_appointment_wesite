<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtpVerificationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subject_type',
        'subject_id',
        'email',
        'phone',
        'delivery_channels',
        'otp_code_hash',
        'requested_at',
        'expires_at',
        'verified_at',
        'last_attempt_at',
        'failed_attempts',
        'status',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'delivery_channels' => 'array',
            'requested_at' => 'datetime',
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
            'last_attempt_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

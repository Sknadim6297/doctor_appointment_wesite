<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnrollmentEditAccessSession extends Model
{
    public const STATUS_PENDING_OTP = 'pending_otp';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_SUPERSEDED = 'superseded';

    protected $fillable = [
        'enrollment_id',
        'requested_by_user_id',
        'otp_hash',
        'otp_expires_at',
        'otp_failed_attempts',
        'verified_at',
        'granted_by_user_id',
        'session_expires_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'otp_expires_at' => 'datetime',
            'verified_at' => 'datetime',
            'session_expires_at' => 'datetime',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by_user_id');
    }
}

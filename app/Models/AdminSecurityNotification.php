<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminSecurityNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_user_id',
        'actor_user_id',
        'subject_type',
        'subject_id',
        'module_key',
        'action',
        'email',
        'otp_code',
        'otp_expires_at',
        'notified_at',
        'ip_address',
        'device_name',
        'browser_name',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'otp_expires_at' => 'datetime',
            'notified_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
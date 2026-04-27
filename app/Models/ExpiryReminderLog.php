<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpiryReminderLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'reminder_type',
        'enrollment_id',
        'doctor_name',
        'recipient_email',
        'expiry_date',
        'days_before_expiry',
        'status',
        'sent_at',
        'error_message',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
            'sent_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }
}

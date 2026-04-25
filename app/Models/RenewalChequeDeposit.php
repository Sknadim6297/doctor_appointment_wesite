<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RenewalChequeDeposit extends Model
{
    protected $fillable = [
        'enrollment_id',
        'doctor_name',
        'member_no',
        'policy_no',
        'money_reciept_no',
        'cheque_no',
        'bank',
        'bank_branch',
        'cheque_amount',
        'payment_date',
        'cheque_file',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'cheque_amount' => 'decimal:2',
    ];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

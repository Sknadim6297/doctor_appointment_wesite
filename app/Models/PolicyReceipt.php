<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PolicyReceipt extends Model
{
    protected $fillable = [
        'policy_no',
        'enrollment_id',
        'doctor_name',
        'last_renewed_date',
        'receive_date',
        'policy_file',
    ];

    protected $dates = [
        'last_renewed_date',
        'receive_date',
    ];

    protected $casts = [
        'last_renewed_date' => 'date',
        'receive_date' => 'date',
    ];

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class, 'enrollment_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LegalCase extends Model
{
    use HasFactory;

    protected $fillable = [
        'enrollment_id',
        'doctor_name',
        'doctor_phone',
        'doctor_mail',
        'case_number',
        'court_year',
        'court',
        'court_address',
        'case_cat',
        'stage',
        'case_details',
        'advocat_mobile',
        'advocat_mail',
        'appear_date',
        'next_date',
        'filling_date',
        'complainant_name',
        'mail_link',
        'direct_payment',
        'money_reciept_no',
        'payment_cheque_no',
        'direct_payment_bank',
        'bank_branch',
        'direct_payment_amount',
        'check_date',
        'case_link',
        'created_by',
    ];

    protected $casts = [
        'appear_date' => 'date',
        'next_date' => 'date',
        'filling_date' => 'date',
        'check_date' => 'date',
        'direct_payment' => 'boolean',
        'direct_payment_amount' => 'decimal:2',
    ];

    public function doctor()
    {
        return $this->belongsTo(Enrollment::class, 'enrollment_id');
    }
}
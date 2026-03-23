<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    protected $fillable = [
        'customer_id_no',
        'money_rc_no',
        'agent_name',
        'agent_phone_no',
        'doctor_name',
        'doctor_address',
        'country',
        'country_name',
        'state',
        'state_name',
        'city',
        'city_name',
        'postcode',
        'mobile1',
        'mobile2',
        'doctor_email',
        'dob',
        'qualification',
        'qualification_year',
        'medical_registration_no',
        'year_of_reg',
        'clinic_address',
        'aadhar_card_no',
        'pan_card_no',
        'specialization_id',
        'payment_mode',
        'plan',
        'plan_name',
        'coverage_id',
        'service_amount',
        'payment_amount',
        'total_amount',
        'payment_method',
        'payment_cheque',
        'payment_bank_name',
        'payment_branch_name',
        'payment_upi_transaction_id',
        'payment_cash_date',
        'bond_to_mail',
        'auto_sms_enabled',
        'created_by',
    ];

    protected $casts = [
        'qualification_year' => 'array',
        'dob'                => 'date',
        'payment_cash_date'  => 'date',
        'bond_to_mail'       => 'boolean',
        'auto_sms_enabled'   => 'boolean',
        'service_amount'     => 'decimal:2',
        'payment_amount'     => 'decimal:2',
        'total_amount'       => 'decimal:2',
    ];

    public function specialization()
    {
        return $this->belongsTo(Specialization::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

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
        'coverage',
        'service_amount',
        'payment_amount',
        'total_amount',
        'payment_method',
        'payment_cheque',
        'payment_bank_name',
        'payment_branch_name',
        'payment_upi_transaction_id',
        'payment_cash_date',
        'renewal_date',
        'policy_date',
        'last_renewal_date',
        'bond_to_mail',
        'auto_sms_enabled',
        'hide_from_call_sheet',
        'call_sheet_specialization_ids',
        'created_by',
        'status',
        'agent_id',
        'created_by_role',
        'approved_by',
        'approved_at',
        'approval_remarks',
        'rejection_reason',
    ];

    protected $casts = [
        'qualification_year' => 'array',
        'dob'                => 'date',
        'payment_cash_date'  => 'date',
        'renewal_date'       => 'date',
        'policy_date'        => 'date',
        'last_renewal_date'  => 'date',
        'approved_at'        => 'datetime',
        'bond_to_mail'       => 'boolean',
        'auto_sms_enabled'   => 'boolean',
        'hide_from_call_sheet' => 'boolean',
        'call_sheet_specialization_ids' => 'array',
        'service_amount'     => 'decimal:2',
        'payment_amount'     => 'decimal:2',
        'total_amount'       => 'decimal:2',
        'coverage'           => 'decimal:2',
    ];

    public function specialization()
    {
        return $this->belongsTo(Specialization::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function policyReceipts()
    {
        return $this->hasMany(\App\Models\PolicyReceipt::class, 'enrollment_id');
    }

    public function doctorDocuments()
    {
        return $this->hasMany(DoctorDocument::class);
    }

    public function isPendingApproval(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}

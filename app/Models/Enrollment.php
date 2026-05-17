<?php

namespace App\Models;

use App\Support\EnrollmentWorkflow;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'current_step',
        'workflow_status',
        'is_step_incomplete',
        'last_activity_at',
        'completed_steps',
        'draft_data',
    ];

    protected $casts = [
        'qualification_year' => 'array',
        'qualification'      => 'array',
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
        'current_step'       => 'integer',
        'is_step_incomplete' => 'boolean',
        'last_activity_at'   => 'datetime',
        'completed_steps'    => 'array',
        'draft_data'         => 'array',
        'service_amount'     => 'decimal:2',
        'payment_amount'     => 'decimal:2',
        'total_amount'       => 'decimal:2',
        'coverage'           => 'decimal:2',
        'workflow_status'    => 'string',
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

    public function scopeProductionReady(Builder $query): Builder
    {
        return $query
            ->where('workflow_status', 'completed')
            ->where('is_step_incomplete', false)
            ->whereNotNull('approved_at');
    }

    public function policyReceipts()
    {
        return $this->hasMany(\App\Models\PolicyReceipt::class, 'enrollment_id');
    }

    public function doctorDocuments()
    {
        return $this->hasMany(DoctorDocument::class);
    }

    public function editAccessSessions(): HasMany
    {
        return $this->hasMany(EnrollmentEditAccessSession::class);
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

    public function normalizedWorkflowStatus(): string
    {
        return EnrollmentWorkflow::normalize($this->workflow_status);
    }

    public function workflowStatusLabel(): string
    {
        return EnrollmentWorkflow::label($this->workflow_status);
    }

    public function displaySpecializationName(): ?string
    {
        $name = trim((string) ($this->specialization?->name ?? ''));
        if ($name !== '') {
            return $name;
        }

        $planName = trim((string) ($this->plan_name ?? ''));

        return $planName !== '' ? $planName : null;
    }

    public function formattedQualification(): ?string
    {
        $qualification = $this->qualification;

        if (is_string($qualification)) {
            $decoded = json_decode($qualification, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $qualification = $decoded;
            }
        }

        if (is_array($qualification)) {
            $parts = array_filter(array_map(function ($item) {
                if (is_array($item)) {
                    return trim((string) ($item['name'] ?? ''));
                }

                return trim((string) $item);
            }, $qualification));

            return $parts !== [] ? implode(', ', $parts) : null;
        }

        $text = trim((string) ($qualification ?? ''));

        return $text !== '' ? $text : null;
    }

    public function formattedQualificationYears(): ?string
    {
        $years = $this->qualification_year;

        if (is_string($years)) {
            $decoded = json_decode($years, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $years = $decoded;
            }
        }

        if (is_array($years)) {
            $parts = array_filter(array_map(fn ($year) => trim((string) $year), $years));

            return $parts !== [] ? implode(', ', $parts) : null;
        }

        if (is_numeric($years)) {
            return (string) (int) $years;
        }

        $text = trim((string) ($years ?? ''));

        return $text !== '' ? $text : null;
    }

    public function formattedRegistrationLine(): ?string
    {
        $registrationNo = trim((string) ($this->medical_registration_no ?? ''));
        $registrationYear = trim((string) ($this->year_of_reg ?? ''));

        if ($registrationNo !== '' && $registrationYear !== '') {
            return $registrationNo . ' / ' . $registrationYear;
        }

        if ($registrationNo !== '') {
            return $registrationNo;
        }

        if ($registrationYear !== '') {
            return $registrationYear;
        }

        return null;
    }

    public function formattedLocation(): ?string
    {
        $city = trim((string) ($this->city_name ?? ''));
        $state = trim((string) ($this->state_name ?? ''));

        if ($city !== '' && $state !== '') {
            return $city . ', ' . $state;
        }

        return $city !== '' ? $city : ($state !== '' ? $state : null);
    }

    public function formattedQualificationWithYears(): ?string
    {
        $qualification = $this->formattedQualification();
        $years = $this->formattedQualificationYears();

        if ($qualification && $years) {
            return $qualification . ' (' . $years . ')';
        }

        return $qualification ?: $years;
    }

    /**
     * @return array<int, array{name: string, year: string}>
     */
    public function qualificationRowsForForm(): array
    {
        $rows = [];
        $qualification = $this->qualification;
        $years = $this->qualification_year;

        if (empty($qualification) && empty($years)) {
            $step1 = data_get($this->draft_data, 'step1', []);
            if (is_array($step1) && ($step1['qualification'] ?? null) !== null) {
                $qualification = $step1['qualification'];
                $years = $step1['qualification_year'] ?? null;
            }
        }

        if (is_string($qualification)) {
            $decoded = json_decode($qualification, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $qualification = $decoded;
            }
        }

        if (is_string($years)) {
            $decoded = json_decode($years, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $years = $decoded;
            }
        }
        $years = is_array($years) ? array_values($years) : [];

        if (is_array($qualification)) {
            foreach ($qualification as $index => $item) {
                if (is_array($item)) {
                    $name = trim((string) ($item['name'] ?? $item['qualification'] ?? ''));
                    $year = $item['year'] ?? ($years[$index] ?? '');
                } else {
                    $name = trim((string) $item);
                    $year = $years[$index] ?? '';
                }

                if ($name !== '' || (string) $year !== '') {
                    $rows[] = [
                        'name' => $name,
                        'year' => $year !== null && $year !== '' ? (string) $year : '',
                    ];
                }
            }
        } elseif (is_string($qualification) && trim($qualification) !== '') {
            $rows[] = [
                'name' => trim($qualification),
                'year' => isset($years[0]) && $years[0] !== '' ? (string) $years[0] : '',
            ];
        }

        if ($rows === [] && $years !== []) {
            foreach ($years as $year) {
                if ($year !== null && $year !== '') {
                    $rows[] = ['name' => '', 'year' => (string) $year];
                }
            }
        }

        return $rows;
    }
}

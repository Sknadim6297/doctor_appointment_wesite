<?php

namespace App\Models;

use App\Support\DoctorDocumentCatalog;
use App\Support\EnrollmentWorkflow;
use App\Models\PolicyReceipt;
use App\Models\NormalPlan;
use App\Models\HighRiskPlan;
use App\Models\ComboPlan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    protected $fillable = [
        'legacy_user_id',
        'customer_id_no',
        'money_rc_no',
        'doctor_money_reciept_no',
        'doctor_money_reciept_year',
        'policy_no',
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
        'legacy_call_sheet_id',
        'hide_from_call_sheet',
        'call_sheet_specialization_ids',
        'call_sheet_card_slug',
        'call_sheet_month',
        'call_sheet_year',
        'created_by',
        'status',
        'agent_id',
        'created_by_role',
        'approved_by',
        'approved_at',
        'submitted_at',
        'resubmitted_at',
        'held_at',
        'held_by',
        'hold_reason',
        'approval_remarks',
        'rejection_reason',
        'current_step',
        'workflow_status',
        'is_step_incomplete',
        'last_activity_at',
        'workflow_completed_at',
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
        'submitted_at'       => 'datetime',
        'resubmitted_at'     => 'datetime',
        'held_at'            => 'datetime',
        'bond_to_mail'       => 'boolean',
        'auto_sms_enabled'   => 'boolean',
        'hide_from_call_sheet' => 'boolean',
        'call_sheet_specialization_ids' => 'array',
        'current_step'       => 'integer',
        'is_step_incomplete' => 'boolean',
        'last_activity_at'   => 'datetime',
        'workflow_completed_at' => 'datetime',
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

    public function heldByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'held_by');
    }

    /**
     * Marketing call sheet list: legacy call sheet rows and manually added entries only.
     */
    public function scopeVisibleOnCallSheet(Builder $query): Builder
    {
        return $query
            ->where('hide_from_call_sheet', false)
            ->where(function (Builder $outer): void {
                $outer->whereNotNull('legacy_call_sheet_id')
                    ->orWhere(function (Builder $manual): void {
                        $manual
                            ->whereNull('legacy_user_id')
                            ->whereNull('legacy_call_sheet_id')
                            ->whereNull('customer_id_no');
                    });
            });
    }

    /**
     * Active doctors eligible for Doctor List, renewals, and production modules.
     */
    public function scopeProductionReady(Builder $query): Builder
    {
        return $query->where(function (Builder $outer): void {
            $outer->where(function (Builder $standard): void {
                $standard
                    ->where('workflow_status', EnrollmentWorkflow::COMPLETED)
                    ->where('is_step_incomplete', false)
                    ->where('status', 'approved')
                    ->whereNotNull('approved_at')
                    ->whereNotNull('approved_by')
                    ->whereNull('legacy_user_id');

                foreach (DoctorDocumentCatalog::requiredEnrollmentDocumentTypes() as $documentType) {
                    $standard->whereHas('doctorDocuments', function (Builder $docQuery) use ($documentType): void {
                        $docQuery
                            ->where('is_active', true)
                            ->where('document_type', $documentType)
                            ->where('verification_status', DoctorDocumentCatalog::STATUS_APPROVED);
                    });
                }
            });

            $outer->orWhere(function (Builder $legacy): void {
                $legacy
                    ->whereNotNull('legacy_user_id')
                    ->where('workflow_status', EnrollmentWorkflow::COMPLETED)
                    ->where('is_step_incomplete', false)
                    ->where('status', 'approved')
                    ->whereNotNull('approved_at')
                    ->whereNotNull('approved_by');
            });
        });
    }

    /**
     * Enrollments with account/premium listing data (legacy imports use policy_no and payment_amount).
     */
    public function scopeWithAccountListing(Builder $query): Builder
    {
        return $query->where(function (Builder $inner): void {
            $inner->where(function (Builder $receipt): void {
                $receipt->whereNotNull('money_rc_no')->where('money_rc_no', '!=', '');
            })
                ->orWhereNotNull('doctor_money_reciept_no')
                ->orWhere(function (Builder $policy): void {
                    $policy->whereNotNull('policy_no')->where('policy_no', '!=', '');
                })
                ->orWhere('payment_amount', '>', 0);
        });
    }

    /**
     * Enrollments still in the internal CRM pipeline (not yet active doctors).
     */
    public function scopeEnrollmentPipeline(Builder $query): Builder
    {
        return $query->whereNot(function (Builder $sub): void {
            $sub->productionReady();
        });
    }

    public function scopeWithRequiredDocumentsVerified(Builder $query): Builder
    {
        foreach (DoctorDocumentCatalog::requiredEnrollmentDocumentTypes() as $documentType) {
            $query->whereHas('doctorDocuments', function (Builder $docQuery) use ($documentType): void {
                $docQuery
                    ->where('is_active', true)
                    ->where('document_type', $documentType)
                    ->where('verification_status', DoctorDocumentCatalog::STATUS_APPROVED);
            });
        }

        return $query;
    }

    public function isProductionActive(): bool
    {
        if ($this->normalizedWorkflowStatus() !== EnrollmentWorkflow::COMPLETED
            || $this->is_step_incomplete
            || $this->status !== 'approved'
            || !$this->approved_at
            || !$this->approved_by) {
            return false;
        }

        if ($this->legacy_user_id !== null) {
            return true;
        }

        return $this->hasAllRequiredDocumentsVerified();
    }

    public function hasAllRequiredDocumentsVerified(): bool
    {
        foreach (DoctorDocumentCatalog::requiredEnrollmentDocumentTypes() as $documentType) {
            $hasApproved = $this->doctorDocuments()
                ->where('is_active', true)
                ->where('document_type', $documentType)
                ->where('verification_status', DoctorDocumentCatalog::STATUS_APPROVED)
                ->exists();

            if (!$hasApproved) {
                return false;
            }
        }

        return true;
    }

    public function policyReceipts()
    {
        return $this->hasMany(\App\Models\PolicyReceipt::class, 'enrollment_id');
    }

    public function latestPolicyReceipt(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(PolicyReceipt::class, 'enrollment_id')->latestOfMany();
    }

    public function renewalHistories(): HasMany
    {
        return $this->hasMany(RenewalHistory::class)->orderByDesc('renewed_date')->orderByDesc('id');
    }

    public function latestRenewalHistory(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(RenewalHistory::class)->latestOfMany('renewed_date');
    }

    public function planLabel(): string
    {
        return match ((int) $this->plan) {
            1 => 'Normal',
            2 => 'High Risk',
            3 => 'Combo',
            default => '—',
        };
    }

    public function formattedCoverageLabel(): string
    {
        $planId = (int) $this->plan;
        $coverageLakh = (float) ($this->coverage ?? 0);

        if ($coverageLakh > 0) {
            $formatted = rtrim(rtrim(number_format($coverageLakh, 2, '.', ''), '0'), '.');

            return $formatted . ' Lakh';
        }

        if ($planId === 3) {
            return 'As per insurance T/C';
        }

        $coverageId = (int) ($this->coverage_id ?? 0);

        if ($coverageId > 0) {
            $fromPlan = $this->resolveCoverageLakhFromPlanId($planId, $coverageId);

            if ($fromPlan !== null) {
                return rtrim(rtrim(number_format($fromPlan, 2, '.', ''), '0'), '.') . ' Lakh';
            }
        }

        return '—';
    }

    private function resolveCoverageLakhFromPlanId(int $planType, int $coverageId): ?float
    {
        $plan = match ($planType) {
            1 => NormalPlan::query()->find($coverageId),
            2 => HighRiskPlan::query()->find($coverageId),
            3 => ComboPlan::query()->find($coverageId),
            default => null,
        };

        if ($plan === null) {
            return null;
        }

        return (float) $plan->coverage_lakh;
    }

    public function doctorDocuments()
    {
        return $this->hasMany(DoctorDocument::class);
    }

    /**
     * Enrollments created and managed directly by Super Admin (trusted internal workflow).
     */
    public function isAdminManaged(): bool
    {
        return $this->created_by_role === 'super_admin';
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
        $qualification = $this->resolveDisplayValue('qualification');

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
        $years = $this->resolveDisplayValue('qualification_year');

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

    public function displayYearOfReg(): ?string
    {
        $year = $this->resolveDisplayValue('year_of_reg');

        return $year !== null ? (string) (int) $year : null;
    }

    public function paymentMethodLabel(): string
    {
        $method = (int) ($this->resolveDisplayValue('payment_method') ?? 0);

        return match ($method) {
            1 => 'Cheque',
            2 => 'Cash',
            3 => 'UPI',
            default => '—',
        };
    }

    public function displayDateValue(string $attribute, string $format = 'd/m/Y'): ?string
    {
        $value = $this->resolveDisplayValue($attribute);

        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format($format);
        }

        $stringValue = trim((string) $value);
        if ($stringValue === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $stringValue)) {
            try {
                return \Carbon\Carbon::parse($stringValue)->format($format);
            } catch (\Throwable) {
                return $stringValue;
            }
        }

        return $stringValue;
    }

    public function display(string $attribute, string $fallback = '—'): string
    {
        $value = $this->resolveDisplayValue($attribute);

        if ($value === null || $value === '') {
            return $fallback;
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        return (string) $value;
    }

    protected function resolveDisplayValue(string $attribute): mixed
    {
        $value = $this->getAttribute($attribute);

        if ($this->isMeaningfulDisplayValue($attribute, $value)) {
            return $value;
        }

        foreach (['step1', 'step2', 'step3', 'step4'] as $step) {
            $draftValue = data_get($this->draft_data, $step . '.' . $attribute);
            if ($this->isMeaningfulDisplayValue($attribute, $draftValue)) {
                return $draftValue;
            }
        }

        return null;
    }

    protected function isMeaningfulDisplayValue(string $attribute, mixed $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        if ($attribute === 'year_of_reg' && (int) $value === 0) {
            return false;
        }

        return true;
    }

    public function displayMedicalRegistrationNo(): ?string
    {
        $value = $this->resolveDisplayValue('medical_registration_no');

        return $value !== null && trim((string) $value) !== '' ? trim((string) $value) : null;
    }

    public function formattedRegistrationLine(): ?string
    {
        $registrationNo = trim((string) ($this->displayMedicalRegistrationNo() ?? ''));
        $registrationYear = trim((string) ($this->displayYearOfReg() ?? ''));

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

    public function displayMoneyReceiptNo(): ?string
    {
        if (filled($this->doctor_money_reciept_no)) {
            $label = (string) $this->doctor_money_reciept_no;
            if (filled($this->doctor_money_reciept_year)) {
                $label .= ' ('.$this->doctor_money_reciept_year.')';
            }

            return $label;
        }

        return filled($this->money_rc_no) ? (string) $this->money_rc_no : null;
    }

    public function displayPolicyNo(): ?string
    {
        if (filled($this->policy_no)) {
            return (string) $this->policy_no;
        }

        $receipt = $this->relationLoaded('latestPolicyReceipt')
            ? $this->latestPolicyReceipt
            : $this->latestPolicyReceipt()->first();

        return filled($receipt?->policy_no) ? (string) $receipt->policy_no : null;
    }

    public function displayPaymentDate(): ?\Carbon\Carbon
    {
        if ($this->payment_cash_date !== null) {
            return $this->payment_cash_date;
        }

        return $this->policy_date ?? $this->last_renewal_date;
    }

    public function displayPolicyDate(): ?\Carbon\Carbon
    {
        return $this->policy_date ?? $this->last_renewal_date;
    }

    public function profilePhotoUrl(): ?string
    {
        $document = $this->doctorDocuments()
            ->where('is_active', true)
            ->whereNotNull('document_file')
            ->where(function ($query) {
                $query->where('mime_type', 'like', 'image/%')
                    ->orWhere('document_file', 'like', '%.jpg')
                    ->orWhere('document_file', 'like', '%.jpeg')
                    ->orWhere('document_file', 'like', '%.png')
                    ->orWhere('document_file', 'like', '%.webp');
            })
            ->orderByDesc('id')
            ->value('document_file');

        if (! filled($document)) {
            return null;
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->exists($document)
            ? \Illuminate\Support\Facades\Storage::url($document)
            : null;
    }
}

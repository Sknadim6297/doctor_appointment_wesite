<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RenewalHistory extends Model
{
    protected $fillable = [
        'enrollment_id',
        'legacy_renewal_id',
        'legacy_doctor_id',
        'renewed_date',
        'renew_month',
        'renew_day',
        'renew_year',
        'medeforum_amount',
        'insurance_amount',
        'coverage_lakh',
        'plan_type',
        'payment_mode',
        'policy_no',
    ];

    protected $casts = [
        'renewed_date' => 'date',
        'medeforum_amount' => 'decimal:2',
        'insurance_amount' => 'decimal:2',
        'coverage_lakh' => 'decimal:2',
        'plan_type' => 'integer',
    ];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function planLabel(): string
    {
        return match ((int) $this->plan_type) {
            1 => 'Normal',
            2 => 'High Risk',
            3 => 'Combo',
            default => '—',
        };
    }

    public function formattedCoverageLabel(): string
    {
        if ($this->coverage_lakh === null || (float) $this->coverage_lakh <= 0) {
            return '—';
        }

        $value = rtrim(rtrim(number_format((float) $this->coverage_lakh, 2, '.', ''), '0'), '.');

        return $value . ' Lakh';
    }
}

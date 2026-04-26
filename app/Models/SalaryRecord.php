<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryRecord extends Model
{
    protected $fillable = [
        'user_id',
        'salary_year',
        'salary_month',
        'monthly_salary',
        'total_login_day',
        'total_absense',
        'absense_reason',
        'incentive',
        'incentive_for',
        'advance',
        'additional_deduct',
        'additional_deduct_reason',
        'office_duty',
        'bonus',
        'pf',
        'esi',
        'ptax',
        'cheque_no',
        'bank_name',
        'net_salary',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'salary_year' => 'integer',
        'monthly_salary' => 'decimal:2',
        'total_login_day' => 'integer',
        'total_absense' => 'integer',
        'incentive' => 'decimal:2',
        'advance' => 'decimal:2',
        'additional_deduct' => 'decimal:2',
        'office_duty' => 'decimal:2',
        'bonus' => 'decimal:2',
        'pf' => 'decimal:2',
        'esi' => 'decimal:2',
        'ptax' => 'decimal:2',
        'net_salary' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
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

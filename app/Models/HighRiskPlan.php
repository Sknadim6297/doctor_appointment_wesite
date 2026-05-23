<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HighRiskPlan extends Model
{
    protected $fillable = [
        'coverage_lakh',
        'yearly_amount',
        'monthly_amount',
        'two_year_amount',
        'three_year_amount',
        'four_year_amount',
        'five_year_amount',
    ];
}

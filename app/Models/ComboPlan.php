<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ComboPlan extends Model
{
    protected $fillable = [
        'specializations',
        'coverage_lakh',
        'yearly_amount',
        'monthly_amount',
        'two_year_amount',
        'three_year_amount',
        'four_year_amount',
        'five_year_amount',
    ];

    protected $casts = [
        'specializations' => 'array',
    ];

    public function linkedSpecializations(): BelongsToMany
    {
        return $this->belongsToMany(
            Specialization::class,
            'combo_plan_specialization',
            'combo_plan_id',
            'specialization_id'
        );
    }
}

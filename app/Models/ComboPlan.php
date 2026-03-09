<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComboPlan extends Model
{
    protected $fillable = [
        'specializations',
        'coverage_lakh',
        'yearly_amount',
    ];

    protected $casts = [
        'specializations' => 'array',
    ];
}

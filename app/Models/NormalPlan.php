<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NormalPlan extends Model
{
    protected $fillable = [
        'coverage_lakh',
        'yearly_amount',
    ];
}

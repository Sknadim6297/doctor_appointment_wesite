<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InsurancePlan extends Model
{
    protected $fillable = [
        'specializations',
        'amount_per_lakh',
        'service_tax_percent',
    ];

    protected $casts = [
        'specializations' => 'array',
    ];
}

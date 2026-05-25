<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobApplication extends Model
{
    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'id',
        'name',
        'email',
        'mobile',
        'salary',
        'document',
        'applied_at',
    ];

    protected $casts = [
        'salary' => 'decimal:2',
        'applied_at' => 'datetime',
    ];
}

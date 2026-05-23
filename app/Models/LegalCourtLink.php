<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegalCourtLink extends Model
{
    public $incrementing = false;

    protected $fillable = ['id', 'name', 'url'];
}

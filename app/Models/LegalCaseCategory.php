<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegalCaseCategory extends Model
{
    public $incrementing = false;

    protected $fillable = ['id', 'name'];

    public function cases(): HasMany
    {
        return $this->hasMany(LegalCase::class, 'legal_case_category_id');
    }
}

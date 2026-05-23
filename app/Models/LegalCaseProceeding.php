<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegalCaseProceeding extends Model
{
    public $incrementing = false;

    protected $fillable = [
        'id',
        'legal_case_id',
        'legacy_case_id',
        'body',
        'proceed_date',
        'legacy_created_by',
        'legacy_edited_by',
    ];

    protected $casts = [
        'proceed_date' => 'date',
    ];

    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class);
    }
}

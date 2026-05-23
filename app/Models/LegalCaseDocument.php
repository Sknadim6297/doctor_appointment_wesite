<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegalCaseDocument extends Model
{
    public $incrementing = false;

    protected $fillable = [
        'id',
        'legal_case_id',
        'legacy_case_id',
        'document_title',
        'file_slug',
    ];

    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegalCasePayment extends Model
{
    public $incrementing = false;

    protected $fillable = [
        'id',
        'legal_case_id',
        'legacy_case_id',
        'cheque_no',
        'bank_name',
        'amount',
        'payment_date',
        'acknowledge_reciept',
    ];

    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class);
    }
}

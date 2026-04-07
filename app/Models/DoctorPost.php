<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorPost extends Model
{
    protected $fillable = [
        'enrollment_id',
        'doctor_name',
        'post_doc_date',
        'post_doc_consignment_no',
        'post_doc_by',
        'post_doc_recieved_date',
        'post_doc_recieved_by',
        'post_doc_remark',
        'tracking_link',
        'post_doc_file',
        'created_by',
    ];

    protected $casts = [
        'post_doc_date' => 'date',
        'post_doc_recieved_date' => 'date',
    ];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

<?php

namespace App\Models;

use App\Support\DoctorDocumentCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class DoctorDocument extends Model
{
    protected $fillable = [
        'enrollment_id',
        'document_category',
        'document_type',
        'document_title',
        'document_file',
        'verification_status',
        'verification_remarks',
        'verified_by',
        'verified_at',
        'original_filename',
        'mime_type',
        'file_size',
        'source',
        'source_key',
        'source_reference_type',
        'source_reference_id',
        'replaces_document_id',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'is_active' => 'boolean',
        'file_size' => 'integer',
    ];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function replacesDocument(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replaces_document_id');
    }

    public function belongsToEnrollment(Enrollment $enrollment): bool
    {
        return (int) $this->enrollment_id === (int) $enrollment->id;
    }

    public function isRejected(): bool
    {
        return $this->verification_status === DoctorDocumentCatalog::STATUS_REJECTED;
    }

    public function isPending(): bool
    {
        return $this->verification_status === DoctorDocumentCatalog::STATUS_PENDING;
    }

    public function fileExists(): bool
    {
        return $this->document_file && Storage::disk('public')->exists($this->document_file);
    }

    public function categoryLabel(): string
    {
        return DoctorDocumentCatalog::categoryLabels()[$this->document_category]
            ?? DoctorDocumentCatalog::categoryLabels()[DoctorDocumentCatalog::CATEGORY_ADDITIONAL];
    }

    public function statusLabel(): string
    {
        return DoctorDocumentCatalog::statusLabel($this->verification_status);
    }

    public function displayFilename(): string
    {
        return $this->original_filename ?: $this->document_title ?: basename((string) $this->document_file);
    }
}

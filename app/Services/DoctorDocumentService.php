<?php

namespace App\Services;

use App\Models\DoctorDocument;
use App\Models\DoctorPost;
use App\Models\Enrollment;
use App\Models\PolicyReceipt;
use App\Models\User;
use App\Support\DoctorDocumentCatalog;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DoctorDocumentService
{
  private const MAX_FILE_KB = 10240;

  private const ALLOWED_MIMES = 'pdf,jpg,jpeg,png,doc,docx';

  public function persistEnrollmentStep1Documents(Request $request, Enrollment $enrollment): void
  {
    foreach (DoctorDocumentCatalog::enrollmentFieldMap() as $field => $meta) {
      if ($field === 'doc_other_forms' || $field === 'doc_other_documents') {
        if (!$request->hasFile($field)) {
          continue;
        }

        foreach ($request->file($field) as $index => $file) {
          if (!$file instanceof UploadedFile || !$file->isValid()) {
            continue;
          }

          $title = $meta['title'] . ' #' . ((int) $index + 1);
          $this->storeUpload(
            $enrollment,
            $file,
            $meta['category'],
            $meta['type'],
            $title,
            DoctorDocumentCatalog::SOURCE_ENROLLMENT_STEP1,
            null,
            null,
            $field . '.' . $index
          );
        }

        continue;
      }

      if (!$request->hasFile($field)) {
        continue;
      }

      $file = $request->file($field);
      if (!$file instanceof UploadedFile || !$file->isValid()) {
        continue;
      }

      $this->storeUpload(
        $enrollment,
        $file,
        $meta['category'],
        $meta['type'],
        $meta['title'],
        DoctorDocumentCatalog::SOURCE_ENROLLMENT_STEP1,
        null,
        null,
        $field
      );
    }
  }

  public function syncPolicyReceipt(PolicyReceipt $policy): ?DoctorDocument
  {
    if (!$policy->policy_file || !$policy->enrollment_id) {
      return null;
    }

    $enrollment = $policy->enrollment ?? Enrollment::find($policy->enrollment_id);
    if (!$enrollment) {
      return null;
    }

    $title = 'Policy Document' . ($policy->policy_no ? ' — ' . $policy->policy_no : '');

    return $this->upsertFromExistingPath(
      $enrollment,
      $policy->policy_file,
      DoctorDocumentCatalog::CATEGORY_ENROLLMENT_FORM,
      'policy',
      $title,
      DoctorDocumentCatalog::SOURCE_ENROLLMENT_STEP3,
      PolicyReceipt::class,
      (int) $policy->id,
      basename($policy->policy_file)
    );
  }

  public function syncDoctorPost(DoctorPost $post): ?DoctorDocument
  {
    if (!$post->post_doc_file || !$post->enrollment_id) {
      return null;
    }

    $enrollment = $post->enrollment ?? Enrollment::find($post->enrollment_id);
    if (!$enrollment) {
      return null;
    }

    $title = $post->post_doc_remark ?: 'Post / Consignment Document';
    if ($post->post_doc_consignment_no) {
      $title .= ' — ' . $post->post_doc_consignment_no;
    }

    $type = $post->post_doc_consignment_no ? 'consignment' : 'post_document';

    return $this->upsertFromExistingPath(
      $enrollment,
      $post->post_doc_file,
      DoctorDocumentCatalog::CATEGORY_ADDITIONAL,
      $type,
      $title,
      DoctorDocumentCatalog::SOURCE_ENROLLMENT_STEP4,
      DoctorPost::class,
      (int) $post->id,
      basename($post->post_doc_file)
    );
  }

  /**
   * Backfill doctor_documents from policy receipts and posts not yet linked.
   */
    public function syncMissingWorkflowDocuments(Enrollment $enrollment): void
    {
        DoctorDocument::query()
            ->where('enrollment_id', $enrollment->id)
            ->whereIn('document_type', ['2', '4', '6', '7', '8'])
            ->where('document_category', DoctorDocumentCatalog::CATEGORY_ADDITIONAL)
            ->each(function (DoctorDocument $doc): void {
                $doc->update([
                    'document_category' => DoctorDocumentCatalog::legacyTypeToCategory((string) $doc->document_type),
                ]);
            });

        PolicyReceipt::query()
      ->where('enrollment_id', $enrollment->id)
      ->whereNotNull('policy_file')
      ->orderBy('id')
      ->each(fn (PolicyReceipt $policy) => $this->syncPolicyReceipt($policy));

    DoctorPost::query()
      ->where('enrollment_id', $enrollment->id)
      ->whereNotNull('post_doc_file')
      ->orderBy('id')
      ->each(fn (DoctorPost $post) => $this->syncDoctorPost($post));
  }

  public function storeUpload(
    Enrollment $enrollment,
    UploadedFile $file,
    string $category,
    string $documentType,
    string $title,
    string $source = DoctorDocumentCatalog::SOURCE_MANUAL,
    ?string $referenceType = null,
    ?int $referenceId = null,
    ?string $sourceKey = null,
    ?DoctorDocument $replaces = null,
  ): DoctorDocument {
    $this->assertValidFile($file);

    $storedPath = $file->store('doctor_documents/' . $enrollment->id, 'public');

    if ($replaces) {
      $replaces->update(['is_active' => false]);
    }

    if ($sourceKey && $referenceType === null) {
      $existing = DoctorDocument::query()
        ->where('enrollment_id', $enrollment->id)
        ->where('source', $source)
        ->where('document_type', $documentType)
        ->where('is_active', true)
        ->when($sourceKey, fn ($q) => $q->where('source_key', $sourceKey))
        ->first();

      if ($existing && !$replaces) {
        $existing->update(['is_active' => false]);
      }
    }

    return DoctorDocument::create([
      'enrollment_id' => $enrollment->id,
      'document_category' => $category,
      'document_type' => $documentType,
      'document_title' => $title,
      'document_file' => $storedPath,
      'verification_status' => DoctorDocumentCatalog::STATUS_PENDING,
      'original_filename' => $file->getClientOriginalName(),
      'mime_type' => $file->getClientMimeType(),
      'file_size' => $file->getSize(),
      'source' => $source,
      'source_key' => $sourceKey,
      'source_reference_type' => $referenceType,
      'source_reference_id' => $referenceId,
      'replaces_document_id' => $replaces?->id,
      'is_active' => true,
      'created_by' => Auth::id(),
    ]);
  }

  private function upsertFromExistingPath(
    Enrollment $enrollment,
    string $path,
    string $category,
    string $documentType,
    string $title,
    string $source,
    ?string $referenceType,
    int $referenceId,
    string $originalFilename,
  ): DoctorDocument {
    $existing = DoctorDocument::query()
      ->where('enrollment_id', $enrollment->id)
      ->where('source_reference_type', $referenceType)
      ->where('source_reference_id', $referenceId)
      ->where('is_active', true)
      ->first();

    $disk = Storage::disk('public');
    $mimeType = $disk->exists($path) ? ($disk->mimeType($path) ?: null) : null;
    $fileSize = $disk->exists($path) ? $disk->size($path) : null;

    $payload = [
      'document_category' => $category,
      'document_type' => $documentType,
      'document_title' => $title,
      'document_file' => $path,
      'original_filename' => $originalFilename,
      'mime_type' => $mimeType,
      'file_size' => $fileSize,
      'source' => $source,
      'source_reference_type' => $referenceType,
      'source_reference_id' => $referenceId,
      'is_active' => true,
    ];

    if ($existing) {
      $existing->fill($payload)->save();

      return $existing;
    }

    return DoctorDocument::create(array_merge($payload, [
      'enrollment_id' => $enrollment->id,
      'verification_status' => DoctorDocumentCatalog::STATUS_PENDING,
      'created_by' => Auth::id(),
    ]));
  }

  /**
   * @return array<string, \Illuminate\Support\Collection<int, DoctorDocument>>
   */
  public function groupedForEnrollment(Enrollment $enrollment): array
  {
    $this->syncMissingWorkflowDocuments($enrollment);

    $documents = DoctorDocument::query()
      ->with(['creator', 'verifier', 'replacesDocument'])
      ->where('enrollment_id', $enrollment->id)
      ->where('is_active', true)
      ->orderByDesc('created_at')
      ->get();

    $grouped = [];
    foreach (DoctorDocumentCatalog::categoryOrder() as $category) {
      $grouped[$category] = $documents->where('document_category', $category)->values();
    }

    $uncategorized = $documents->filter(fn (DoctorDocument $doc) => !in_array($doc->document_category, DoctorDocumentCatalog::categoryOrder(), true));
    if ($uncategorized->isNotEmpty()) {
      $grouped[DoctorDocumentCatalog::CATEGORY_ADDITIONAL] = $grouped[DoctorDocumentCatalog::CATEGORY_ADDITIONAL]
        ->merge($uncategorized)
        ->values();
    }

    return $grouped;
  }

  public function assertValidFile(UploadedFile $file): void
  {
    $extension = strtolower($file->getClientOriginalExtension() ?: '');
    $allowed = explode(',', self::ALLOWED_MIMES);

    if (!in_array($extension, $allowed, true)) {
      throw new \InvalidArgumentException('Unsupported file type.');
    }

    if ($file->getSize() > self::MAX_FILE_KB * 1024) {
      throw new \InvalidArgumentException('File exceeds maximum upload size.');
    }
  }

  public function resolveDisplayName(DoctorDocument $document): string
  {
    if ($document->original_filename) {
      return $document->original_filename;
    }

    return $document->document_title ?: 'Document';
  }
}

<?php

namespace App\Services;

use App\Models\DoctorDocument;
use App\Models\DoctorPost;
use App\Models\Enrollment;
use App\Models\PolicyReceipt;
use App\Models\User;
use App\Support\DoctorDocumentCatalog;
use App\Support\SecureFileUpload;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DoctorDocumentService
{
    private const MAX_FILE_KB = 10240;

    private const ALLOWED_MIMES = 'pdf,jpg,jpeg,png,doc,docx';

    public function promoteToActiveDoctorIfEligible(Enrollment $enrollment): bool
    {
        $enrollment->refresh();

        if (!$enrollment->isProductionActive()) {
            if ($enrollment->normalizedWorkflowStatus() === \App\Support\EnrollmentWorkflow::COMPLETED) {
                $enrollment->update([
                    'is_step_incomplete' => !$enrollment->hasAllRequiredDocumentsVerified(),
                ]);
            }

            return false;
        }

        if (!$enrollment->approved_at) {
            $enrollment->update([
                'status' => 'approved',
                'approved_by' => $enrollment->approved_by ?? Auth::id(),
                'approved_at' => now(),
                'is_step_incomplete' => false,
            ]);
        }

        return true;
    }

    public function requestHasEnrollmentStep1Files(Request $request): bool
    {
        foreach (array_keys(DoctorDocumentCatalog::enrollmentFieldMap()) as $field) {
            if ($request->hasFile($field)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{grouped: array<string, \Illuminate\Support\Collection>, slots: list<array>, documents: \Illuminate\Support\Collection<int, DoctorDocument>}
     */
    public function enrollmentDocumentsSummary(Enrollment $enrollment): array
    {
        $grouped = $this->groupedForEnrollment($enrollment);

        $documents = collect($grouped)->flatten(1)->values();

        $slots = [];
        foreach (DoctorDocumentCatalog::enrollmentUploadSlots() as $slot) {
            $matches = $documents->filter(function (DoctorDocument $doc) use ($slot): bool {
                if (($slot['field'] ?? '') !== '' && $doc->source_key === $slot['field']) {
                    return true;
                }

                if (!empty($slot['multiple']) && is_string($doc->source_key) && str_starts_with($doc->source_key, $slot['field'] . '.')) {
                    return true;
                }

                return $doc->document_type === ($slot['type'] ?? '')
                    && $doc->source === DoctorDocumentCatalog::SOURCE_ENROLLMENT_STEP1;
            })->values();

            $slots[] = array_merge($slot, ['documents' => $matches]);
        }

        return [
            'grouped' => $grouped,
            'slots' => $slots,
            'documents' => $documents,
        ];
    }

    /**
     * Required Step 1 identity documents that must exist before admin approval.
     *
     * @return list<string> Human-readable missing document titles
     */
    public function missingRequiredEnrollmentDocuments(Enrollment $enrollment): array
    {
        $this->syncMissingWorkflowDocuments($enrollment);

        $labels = [
            'aadhaar' => 'Aadhaar Card',
            'pan' => 'PAN Card',
            'medical_registration' => 'Medical Registration Certificate',
        ];

        $missing = [];

        foreach (DoctorDocumentCatalog::requiredEnrollmentDocumentTypes() as $documentType) {
            $exists = DoctorDocument::query()
                ->where('enrollment_id', $enrollment->id)
                ->where('is_active', true)
                ->where('document_type', $documentType)
                ->where('source', DoctorDocumentCatalog::SOURCE_ENROLLMENT_STEP1)
                ->whereNotNull('document_file')
                ->exists();

            if (!$exists) {
                $missing[] = $labels[$documentType] ?? $documentType;
            }
        }

        return $missing;
    }

    public function persistEnrollmentStep1Documents(Request $request, Enrollment $enrollment): void
    {
        SecureFileUpload::assertRequestWithinTotalSize($request);

        foreach (DoctorDocumentCatalog::enrollmentFieldMap() as $field => $meta) {
            if ($field === 'doc_other_forms' || $field === 'doc_other_documents') {
                if (!$request->hasFile($field)) {
                    continue;
                }

                foreach ($request->file($field) as $index => $file) {
                    if (!$file instanceof UploadedFile || !$file->isValid()) {
                        continue;
                    }

                    $title = DoctorDocumentCatalog::indexedTitle($meta['title'], (int) $index + 1);
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

        $this->promoteToActiveDoctorIfEligible($enrollment);
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

        $title = 'Policy Certificate' . ($policy->policy_no ? ' — ' . $policy->policy_no : '');

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

        $title = $post->post_doc_remark ?: ($post->post_doc_consignment_no ? 'Consignment Note' : 'Processing Note');
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
                    'document_title' => DoctorDocumentCatalog::humanizeTitle(
                        (string) $doc->document_title,
                        (string) $doc->document_type
                    ),
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

        $this->backfillTrustedDocumentApprovals($enrollment);
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

        $title = DoctorDocumentCatalog::humanizeTitle($title, $documentType, $sourceKey);

        return DoctorDocument::create(array_merge([
            'enrollment_id' => $enrollment->id,
            'document_category' => $category,
            'document_type' => $documentType,
            'document_title' => $title,
            'document_file' => $storedPath,
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
        ], $this->verificationDefaults($enrollment, $source, $replaces)));
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

        $title = DoctorDocumentCatalog::humanizeTitle($title, $documentType);

        $payload = array_merge([
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
        ], $this->verificationDefaults($enrollment, $source));

        if ($existing) {
            $existing->fill($payload)->save();

            return $existing;
        }

        return DoctorDocument::create(array_merge($payload, [
            'enrollment_id' => $enrollment->id,
            'created_by' => Auth::id(),
        ]));
    }

    /**
     * @return array{verification_status: string, verified_by: ?int, verified_at: ?\Illuminate\Support\Carbon}
     */
    private function verificationDefaults(Enrollment $enrollment, string $source, ?DoctorDocument $replaces = null): array
    {
        if ($this->shouldAutoApprove($enrollment, $source, $replaces)) {
            return [
                'verification_status' => DoctorDocumentCatalog::STATUS_APPROVED,
                'verified_by' => Auth::id(),
                'verified_at' => now(),
            ];
        }

        return [
            'verification_status' => DoctorDocumentCatalog::STATUS_PENDING,
            'verified_by' => null,
            'verified_at' => null,
        ];
    }

    private function shouldAutoApprove(Enrollment $enrollment, string $source, ?DoctorDocument $replaces = null): bool
    {
        if ($replaces !== null) {
            return false;
        }

        if (DoctorDocumentCatalog::isExternalSource($source)) {
            return false;
        }

        if ($enrollment->isAdminManaged() || $this->authIsSuperAdmin()) {
            return true;
        }

        return false;
    }

    private function authIsSuperAdmin(): bool
    {
        $user = Auth::user();

        if (!$user instanceof User) {
            return false;
        }

        return (method_exists($user, 'hasAdminRole') && $user->hasAdminRole('super_admin'))
            || (($user->role ?? null) === 'super_admin');
    }

    public function backfillTrustedDocumentApprovals(Enrollment $enrollment): void
    {
        if (!$enrollment->isAdminManaged() && !$this->authIsSuperAdmin()) {
            return;
        }

        DoctorDocument::query()
            ->where('enrollment_id', $enrollment->id)
            ->where('is_active', true)
            ->where('verification_status', DoctorDocumentCatalog::STATUS_PENDING)
            ->whereNull('replaces_document_id')
            ->whereNotIn('source', DoctorDocumentCatalog::externalVerificationSources())
            ->update([
                'verification_status' => DoctorDocumentCatalog::STATUS_APPROVED,
                'verified_by' => Auth::id(),
                'verified_at' => now(),
                'verification_remarks' => null,
            ]);

        DoctorDocument::query()
            ->where('enrollment_id', $enrollment->id)
            ->where('is_active', true)
            ->get()
            ->each(function (DoctorDocument $doc): void {
                $humanized = DoctorDocumentCatalog::humanizeTitle(
                    (string) $doc->document_title,
                    (string) $doc->document_type,
                    $doc->source_key
                );

                if ($humanized !== $doc->document_title) {
                    $doc->update(['document_title' => $humanized]);
                }
            });
    }

    /**
     * @return array<string, \Illuminate\Support\Collection<int, DoctorDocument>>
     */
    public function groupedForEnrollment(Enrollment $enrollment): array
    {
        $this->syncMissingWorkflowDocuments($enrollment);

        $documents = DoctorDocument::query()
            ->with(['creator', 'verifier', 'replacesDocument', 'enrollment'])
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
        SecureFileUpload::assertValid($file);
    }

    public function resolveDisplayName(DoctorDocument $document): string
    {
        if ($document->original_filename) {
            return $document->original_filename;
        }

        return $document->displayTitle() ?: 'Document';
    }
}

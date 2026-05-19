<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DoctorDocument;
use App\Models\Enrollment;
use App\Services\DoctorDocumentService;
use App\Services\EnrollmentRecordAccessService;
use App\Support\DoctorDocumentCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DoctorDocumentController extends Controller
{
    public function __construct(
        private readonly DoctorDocumentService $doctorDocumentService,
        private readonly EnrollmentRecordAccessService $recordAccess,
    ) {
    }

    public function storeForDoctor(Request $request, Enrollment $doctor)
    {
        $this->recordAccess->assertCanAccessRecord($request->user(), $doctor);
        $data = $request->validate([
            'document_category' => 'required|string|in:' . implode(',', DoctorDocumentCatalog::categoryOrder()),
            'document_type' => 'nullable|string|max:64',
            'document_title' => 'required|string|max:255',
            'document_file' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
        ]);

        $legacyType = $request->input('document_type');
        if ($legacyType !== null && $legacyType !== '' && is_numeric($legacyType)) {
            $data['document_category'] = DoctorDocumentCatalog::legacyTypeToCategory((string) $legacyType);
            $documentType = (string) $legacyType;
            if (trim($data['document_title']) === 'Documents dispatched for enrollment processing.') {
                $data['document_title'] = DoctorDocumentCatalog::legacyTypeLabel((string) $legacyType);
            }
        } else {
            $documentType = filled($data['document_type'] ?? null)
                ? (string) $data['document_type']
                : Str::slug($data['document_title']);
        }

        $this->doctorDocumentService->storeUpload(
            $doctor,
            $request->file('document_file'),
            $data['document_category'],
            $documentType,
            $data['document_title'],
            DoctorDocumentCatalog::SOURCE_MANUAL,
        );

        return redirect()
            ->route('admin.doctors.show', ['doctor' => $doctor->id, 'tab' => 'doctor_documents'])
            ->with('success', 'Document uploaded and linked to this doctor profile.');
    }

    public function view(Enrollment $doctor, DoctorDocument $document)
    {
        $this->recordAccess->assertCanAccessRecord(request()->user(), $doctor);
        $this->authorizeDocument($doctor, $document);

        if (!$document->fileExists()) {
            abort(404, 'File not found.');
        }

        $disk = Storage::disk('public');
        $mime = $document->mime_type ?: $disk->mimeType($document->document_file) ?: 'application/octet-stream';

        return response()->file($disk->path($document->document_file), [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . addslashes($document->displayFilename()) . '"',
        ]);
    }

    public function download(Enrollment $doctor, DoctorDocument $document): StreamedResponse
    {
        $this->recordAccess->assertCanAccessRecord(request()->user(), $doctor);
        $this->authorizeDocument($doctor, $document);

        if (!$document->fileExists()) {
            abort(404, 'File not found.');
        }

        return Storage::disk('public')->download(
            $document->document_file,
            $document->displayFilename()
        );
    }

    public function approve(Request $request, Enrollment $doctor, DoctorDocument $document)
    {
        $this->recordAccess->assertCanAccessRecord($request->user(), $doctor);
        $this->authorizeDocument($doctor, $document);

        $data = $request->validate([
            'verification_remarks' => 'nullable|string|max:2000',
        ]);

        $document->update([
            'verification_status' => DoctorDocumentCatalog::STATUS_APPROVED,
            'verification_remarks' => $data['verification_remarks'] ?? null,
            'verified_by' => Auth::id(),
            'verified_at' => now(),
        ]);

        return redirect()
            ->route('admin.doctors.show', ['doctor' => $doctor->id, 'tab' => 'doctor_documents'])
            ->with('success', 'Document marked as approved.');
    }

    public function reject(Request $request, Enrollment $doctor, DoctorDocument $document)
    {
        $this->recordAccess->assertCanAccessRecord($request->user(), $doctor);
        $this->authorizeDocument($doctor, $document);

        $data = $request->validate([
            'verification_remarks' => 'required|string|max:2000',
        ]);

        $document->update([
            'verification_status' => DoctorDocumentCatalog::STATUS_REJECTED,
            'verification_remarks' => $data['verification_remarks'],
            'verified_by' => Auth::id(),
            'verified_at' => now(),
        ]);

        return redirect()
            ->route('admin.doctors.show', ['doctor' => $doctor->id, 'tab' => 'doctor_documents'])
            ->with('success', 'Document rejected. The uploader may submit a replacement.');
    }

    public function reupload(Request $request, Enrollment $doctor, DoctorDocument $document)
    {
        $this->recordAccess->assertCanAccessRecord($request->user(), $doctor);
        $this->authorizeDocument($doctor, $document);

        if (!$document->isRejected()) {
            return redirect()
                ->route('admin.doctors.show', ['doctor' => $doctor->id, 'tab' => 'doctor_documents'])
                ->with('error', 'Only rejected documents can be re-uploaded.');
        }

        $data = $request->validate([
            'document_file' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
        ]);

        $this->doctorDocumentService->storeUpload(
            $doctor,
            $request->file('document_file'),
            $document->document_category,
            $document->document_type,
            $document->document_title,
            $document->source,
            $document->source_reference_type,
            $document->source_reference_id ? (int) $document->source_reference_id : null,
            $document->source_key,
            $document,
        );

        return redirect()
            ->route('admin.doctors.show', ['doctor' => $doctor->id, 'tab' => 'doctor_documents'])
            ->with('success', 'Replacement document uploaded and pending verification.');
    }

    private function authorizeDocument(Enrollment $doctor, DoctorDocument $document): void
    {
        if (!$document->belongsToEnrollment($doctor)) {
            abort(404);
        }
    }
}

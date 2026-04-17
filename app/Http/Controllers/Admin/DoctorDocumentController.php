<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DoctorDocument;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DoctorDocumentController extends Controller
{
    public function storeForDoctor(Request $request, Enrollment $doctor)
    {
        $data = $request->validate([
            'document_type' => 'required|integer|in:2,4,6,7,8',
            'document_title' => 'required|string|max:255',
            'document_file' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
        ]);

        $filePath = $request->file('document_file')->store('doctor_documents', 'public');

        DoctorDocument::create([
            'enrollment_id' => $doctor->id,
            'document_type' => (string) $data['document_type'],
            'document_title' => $data['document_title'],
            'document_file' => $filePath,
            'created_by' => Auth::id(),
        ]);

        return redirect()
            ->route('admin.doctors.show', ['doctor' => $doctor->id, 'tab' => 'doctor_documents'])
            ->with('success', 'Document uploaded successfully.');
    }
}
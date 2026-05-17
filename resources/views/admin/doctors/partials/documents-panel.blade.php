@php
    use App\Support\DoctorDocumentCatalog;
    $groupedDocuments = $groupedDocuments ?? [];
    $documentCategoryLabels = $documentCategoryLabels ?? DoctorDocumentCatalog::categoryLabels();
    $canVerifyDocuments = $canVerifyDocuments ?? false;
    $totalDocs = collect($groupedDocuments)->flatten(1)->count();
@endphp

<style>
    .doctor-documents-panel { max-width: 100%; }
    .doctor-documents-panel .doc-table-scroll {
        overflow-x: auto;
        max-width: 100%;
        -webkit-overflow-scrolling: touch;
    }
    .doctor-documents-panel .doc-table {
        width: 100%;
        min-width: 720px;
        border-collapse: collapse;
        font-size: 0.82rem;
    }
    .doctor-documents-panel .doc-table th,
    .doctor-documents-panel .doc-table td {
        border: 1px solid #dbe3ee;
        padding: 0.55rem 0.65rem;
        vertical-align: top;
        text-align: left;
        white-space: normal;
    }
    .doctor-documents-panel .doc-table th {
        background: #1e3a8a;
        color: #fff;
        font-size: 0.76rem;
        text-transform: uppercase;
    }
    .doctor-documents-panel .col-document { min-width: 11rem; max-width: 16rem; }
    .doctor-documents-panel .col-uploaded { white-space: nowrap; }
    .doctor-documents-panel .col-actions { white-space: nowrap; text-align: right; }
    .doctor-documents-panel .doc-filename { word-break: break-word; font-size: 0.75rem; color: #64748b; }
    .doctor-documents-panel .doc-actions { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 0.25rem; }
</style>

<div class="doctor-documents-panel">
    <div class="mb-6 rounded-2xl border border-slate-200 bg-slate-50/80 p-5">
        <h4 class="text-base font-bold text-slate-900">Upload document</h4>
        <p class="mt-1 text-sm text-slate-600">Files are stored on this doctor profile and appear in the sections below.</p>
        <form action="{{ route('admin.doctors.documents.store', $doctor->id) }}" method="POST" enctype="multipart/form-data" class="mt-4 grid gap-4 md:grid-cols-2">
            @csrf
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Category</label>
                <select class="modal-input w-full" name="document_category" required>
                    @foreach($documentCategoryLabels as $key => $label)
                        <option value="{{ $key }}" @selected(old('document_category') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Document name</label>
                <input type="text" class="modal-input w-full" name="document_title" value="{{ old('document_title') }}" placeholder="e.g. Cheque copy" required>
            </div>
            <div class="md:col-span-2">
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">File</label>
                <input type="file" class="modal-input w-full" name="document_file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
            </div>
            <div class="md:col-span-2">
                <button type="submit" class="qbtn qbtn-blue">Upload to profile</button>
            </div>
        </form>
    </div>

    @if($totalDocs === 0)
        <div class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center text-sm text-slate-500">
            No documents linked yet. Upload during enrollment or use the form above.
        </div>
    @endif

    @foreach(DoctorDocumentCatalog::categoryOrder() as $categoryKey)
        @php $items = $groupedDocuments[$categoryKey] ?? collect(); @endphp
        @if($items->isEmpty())
            @continue
        @endif
        <section class="mb-8">
            <h4 class="mb-3 flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-slate-700">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-blue-100 text-blue-700">
                    <i class="ri-folder-3-line"></i>
                </span>
                {{ $documentCategoryLabels[$categoryKey] ?? $categoryKey }}
                <span class="rounded-full bg-slate-200 px-2 py-0.5 text-xs font-semibold text-slate-700">{{ $items->count() }}</span>
            </h4>
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="doc-table-scroll">
                    <table class="doc-table">
                        <thead>
                            <tr>
                                <th class="col-document">Document</th>
                                <th class="col-uploaded">Uploaded</th>
                                <th>Status</th>
                                <th>By</th>
                                <th class="col-actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $document)
                                @php
                                    $status = $document->verification_status ?? DoctorDocumentCatalog::STATUS_PENDING;
                                    $statusClass = match ($status) {
                                        DoctorDocumentCatalog::STATUS_APPROVED => 'bg-emerald-100 text-emerald-800',
                                        DoctorDocumentCatalog::STATUS_REJECTED => 'bg-rose-100 text-rose-800',
                                        default => 'bg-amber-100 text-amber-800',
                                    };
                                @endphp
                                <tr>
                                    <td class="col-document">
                                        <p class="font-semibold text-slate-900">{{ $document->document_title }}</p>
                                        <p class="doc-filename">{{ $document->displayFilename() }}</p>
                                        @if($document->verification_remarks && $status === DoctorDocumentCatalog::STATUS_REJECTED)
                                            <p class="mt-1 text-xs text-rose-700">{{ $document->verification_remarks }}</p>
                                        @endif
                                    </td>
                                    <td class="col-uploaded text-sm text-slate-600">
                                        {{ optional($document->created_at)->format('d M Y, h:i A') ?? '—' }}
                                    </td>
                                    <td>
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">
                                            {{ $document->statusLabel() }}
                                        </span>
                                    </td>
                                    <td class="text-sm text-slate-600">{{ $document->creator->name ?? 'System' }}</td>
                                    <td class="col-actions">
                                        <div class="doc-actions">
                                            @if($document->fileExists())
                                                <a href="{{ route('admin.doctors.documents.view', [$doctor->id, $document->id]) }}" class="qbtn qbtn-green" title="Preview" target="_blank" rel="noopener"><i class="ri-eye-line"></i></a>
                                                <a href="{{ route('admin.doctors.documents.download', [$doctor->id, $document->id]) }}" class="qbtn qbtn-blue" title="Download"><i class="ri-download-2-line"></i></a>
                                            @endif
                                            @if($canVerifyDocuments && $status === DoctorDocumentCatalog::STATUS_PENDING)
                                                <form action="{{ route('admin.doctors.documents.approve', [$doctor->id, $document->id]) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" class="qbtn qbtn-green text-xs">Approve</button>
                                                </form>
                                                <button type="button" class="qbtn qbtn-rose text-xs" onclick="document.getElementById('reject-doc-{{ $document->id }}').showModal()">Reject</button>
                                            @endif
                                            @if($status === DoctorDocumentCatalog::STATUS_REJECTED)
                                                <button type="button" class="qbtn qbtn-amber text-xs" onclick="document.getElementById('reupload-doc-{{ $document->id }}').showModal()">Re-upload</button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @foreach($items as $document)
                @php $status = $document->verification_status ?? DoctorDocumentCatalog::STATUS_PENDING; @endphp
                @if($canVerifyDocuments && $status === DoctorDocumentCatalog::STATUS_PENDING)
                    <dialog id="reject-doc-{{ $document->id }}" class="rounded-xl border border-slate-200 p-0 shadow-2xl backdrop:bg-black/40">
                        <form action="{{ route('admin.doctors.documents.reject', [$doctor->id, $document->id]) }}" method="POST" class="w-full max-w-md p-6">
                            @csrf
                            <h3 class="text-lg font-bold text-slate-900">Reject document</h3>
                            <p class="mt-1 text-sm text-slate-600">{{ $document->displayFilename() }}</p>
                            <textarea name="verification_remarks" class="modal-input mt-4 w-full" rows="3" placeholder="Reason for rejection (required)" required></textarea>
                            <div class="mt-4 flex gap-2">
                                <button type="button" class="flex-1 rounded-lg border border-slate-300 px-4 py-2" onclick="this.closest('dialog').close()">Cancel</button>
                                <button type="submit" class="flex-1 rounded-lg bg-rose-600 px-4 py-2 font-semibold text-white">Reject</button>
                            </div>
                        </form>
                    </dialog>
                @endif
                @if($status === DoctorDocumentCatalog::STATUS_REJECTED)
                    <dialog id="reupload-doc-{{ $document->id }}" class="rounded-xl border border-slate-200 p-0 shadow-2xl backdrop:bg-black/40">
                        <form action="{{ route('admin.doctors.documents.reupload', [$doctor->id, $document->id]) }}" method="POST" enctype="multipart/form-data" class="w-full max-w-md p-6">
                            @csrf
                            <h3 class="text-lg font-bold text-slate-900">Re-upload document</h3>
                            <p class="mt-1 text-sm text-slate-600">Replace: {{ $document->document_title }}</p>
                            <input type="file" name="document_file" class="modal-input mt-4 w-full" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
                            <div class="mt-4 flex gap-2">
                                <button type="button" class="flex-1 rounded-lg border border-slate-300 px-4 py-2" onclick="this.closest('dialog').close()">Cancel</button>
                                <button type="submit" class="flex-1 rounded-lg bg-amber-600 px-4 py-2 font-semibold text-white">Upload replacement</button>
                            </div>
                        </form>
                    </dialog>
                @endif
            @endforeach
        </section>
    @endforeach
</div>

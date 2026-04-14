@extends('admin.layouts.app')

@section('title', 'Bulk Upload')
@section('page-title', 'Bulk Upload')

@section('content')
<section class="section-card">
    <div class="mb-5">
        <h3 class="section-title mb-1">Bulk Upload</h3>
        <p class="text-sm text-slate-600">Upload doctor data in one go using CSV or Excel files.</p>
    </div>

    <div class="mb-5 flex flex-wrap items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
        <span class="font-semibold text-slate-800">Need a template?</span>
        <a href="{{ route('admin.bulk-upload.template') }}" class="inline-flex items-center gap-2 rounded-full bg-blue-600 px-4 py-2 text-white transition hover:bg-blue-700">
            <i class="ri-download-2-line"></i>
            <span>Download CSV Template</span>
        </a>
        <span class="text-slate-500">Use the same column names shown below for best results.</span>
    </div>

    <div
        x-data="{
            fileName: '',
            fileError: '',
            onFileChange(event) {
                const file = event.target.files[0];
                this.fileError = '';
                this.fileName = '';

                if (!file) return;

                const allowed = ['csv', 'xls', 'xlsx'];
                const ext = (file.name.split('.').pop() || '').toLowerCase();
                const maxSize = 20 * 1024 * 1024;

                if (!allowed.includes(ext)) {
                    this.fileError = 'Please select a CSV or Excel file.';
                    event.target.value = '';
                    return;
                }

                if (file.size > maxSize) {
                    this.fileError = 'File size must be 20MB or less.';
                    event.target.value = '';
                    return;
                }

                this.fileName = file.name;
            }
        }"
        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
    >
        <div class="border-b border-slate-100 bg-slate-50/70 px-6 py-4">
            <h4 class="text-sm font-semibold text-slate-800">Doctor File Import</h4>
            <p class="mt-1 text-xs text-slate-500">Supported formats: .csv, .xls, .xlsx (max 20MB)</p>
        </div>

        <form action="{{ route('admin.bulk-upload.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5 p-6">
            @csrf

            <div>
                <label for="bulk-file" class="sr-only">Choose an Excel/CSV file</label>
                <label
                    for="bulk-file"
                    class="group flex min-h-36 cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-300 bg-slate-50/60 px-4 py-6 text-center transition hover:border-blue-400 hover:bg-blue-50/40"
                >
                    <i class="ri-upload-cloud-2-line text-3xl text-slate-500 transition group-hover:text-blue-600"></i>
                    <p class="mt-2 text-sm font-semibold text-slate-700">Choose an Excel/CSV file</p>
                    <p class="mt-1 text-xs text-slate-500">Click to browse or drag and drop your file here</p>
                    <p x-show="fileName" x-text="fileName" class="mt-3 inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700" style="display: none;"></p>
                </label>

                <input
                    type="file"
                    name="exfile"
                    id="bulk-file"
                    class="hidden"
                    accept=".csv,.xls,.xlsx"
                    required
                    @change="onFileChange($event)"
                >

                <p x-show="fileError" x-text="fileError" class="mt-2 text-xs text-red-600" style="display: none;"></p>
                @error('exfile')
                    <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4">
                <p class="text-xs text-slate-500">Tip: Keep header names consistent with your upload template.</p>
                <button type="submit" class="btn btn-primary !px-6">
                    <i class="ri-upload-2-line"></i>
                    <span>Upload File</span>
                </button>
            </div>
        </form>
    </div>

    <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 bg-slate-50 px-6 py-4">
            <h4 class="text-sm font-semibold text-slate-800">Expected Columns</h4>
            <p class="mt-1 text-xs text-slate-500">The first row should contain these headers. Any extra columns will be ignored.</p>
        </div>
        <div class="grid gap-2 p-5 text-xs text-slate-600 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach($templateHeaders as $header)
                <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 font-mono">{{ $header }}</div>
            @endforeach
        </div>
    </div>

    @if(session('bulk_upload_path'))
        <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            Stored path: {{ session('bulk_upload_path') }}
        </div>
    @endif

    @if(session('bulk_upload_result'))
        @php($bulkUploadResult = session('bulk_upload_result'))
        <div class="mt-4 overflow-hidden rounded-2xl border border-blue-200 bg-blue-50">
            <div class="border-b border-blue-100 px-5 py-4">
                <h4 class="text-sm font-semibold text-blue-900">Import Summary</h4>
                <p class="mt-1 text-xs text-blue-700">Processed {{ $bulkUploadResult['processed_rows'] }} data rows.</p>
            </div>
            <div class="grid gap-3 px-5 py-4 text-sm text-blue-900 sm:grid-cols-3">
                <div class="rounded-xl bg-white px-4 py-3 shadow-sm">
                    <div class="text-xs uppercase tracking-wide text-blue-500">Created</div>
                    <div class="mt-1 text-lg font-semibold">{{ $bulkUploadResult['created_rows'] }}</div>
                </div>
                <div class="rounded-xl bg-white px-4 py-3 shadow-sm">
                    <div class="text-xs uppercase tracking-wide text-blue-500">Updated</div>
                    <div class="mt-1 text-lg font-semibold">{{ $bulkUploadResult['updated_rows'] }}</div>
                </div>
                <div class="rounded-xl bg-white px-4 py-3 shadow-sm">
                    <div class="text-xs uppercase tracking-wide text-blue-500">Skipped</div>
                    <div class="mt-1 text-lg font-semibold">{{ $bulkUploadResult['skipped_rows'] }}</div>
                </div>
            </div>

            @if(!empty($bulkUploadResult['errors']))
                <div class="border-t border-blue-100 px-5 py-4">
                    <h5 class="text-xs font-semibold uppercase tracking-wide text-blue-800">Row Errors</h5>
                    <div class="mt-3 space-y-3">
                        @foreach(array_slice($bulkUploadResult['errors'], 0, 10) as $errorRow)
                            <div class="rounded-xl border border-blue-100 bg-white px-4 py-3 text-sm text-slate-700">
                                <div class="font-semibold text-blue-900">Row {{ $errorRow['row'] }}</div>
                                <ul class="mt-2 list-disc pl-5 text-xs text-slate-600">
                                    @foreach($errorRow['messages'] as $message)
                                        <li>{{ $message }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif
</section>
@endsection

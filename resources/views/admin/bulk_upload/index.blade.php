@extends('admin.layouts.app')

@section('title', 'Bulk Upload')
@section('page-title', 'Bulk Upload')

@section('content')
<section class="section-card">
    <div class="mb-5">
        <h3 class="section-title mb-1">Bulk Upload</h3>
        <p class="text-sm text-slate-600">Upload doctor data in one go using CSV or Excel files.</p>
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

    @if(session('bulk_upload_path'))
        <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            Stored path: {{ session('bulk_upload_path') }}
        </div>
    @endif
</section>
@endsection

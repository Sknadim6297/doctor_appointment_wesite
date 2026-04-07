@extends('admin.layouts.app')

@section('title', 'Bulk Upload')
@section('page-title', 'Bulk Upload')

@section('content')
<section class="section-card">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <h3 class="section-title mb-0">Bulk Upload</h3>
        <button type="button" class="btn btn-default" onclick="document.getElementById('bulk-file').click();">Choose File</button>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-gradient-to-br from-white to-slate-50 p-6 shadow-sm">
        <p class="mb-4 text-sm text-slate-600">Upload doctor data in one go using CSV or Excel files.</p>
        <form action="{{ route('admin.bulk-upload.store') }}" method="POST" enctype="multipart/form-data" class="grid gap-5 md:grid-cols-12 md:items-end">
            @csrf

            <div class="md:col-span-7">
                <label for="bulk-file" class="mb-2 block text-sm font-semibold text-slate-700">Choose an Excel/CSV file</label>
                <input type="file" name="exfile" id="bulk-file" class="form-control" accept=".csv,.xls,.xlsx" required>
                <p class="mt-2 text-xs text-slate-500">Supported formats: .csv, .xls, .xlsx (max 20MB)</p>
                @error('exfile')
                    <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="md:col-span-5 md:text-right">
                <button type="submit" class="btn btn-primary !px-6">
                    <i class="ri-upload-cloud-2-line"></i>
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

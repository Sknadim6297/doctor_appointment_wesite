@extends('admin.layouts.app')

@section('title', 'Submit Post')
@section('page-title', 'Doctor Enrollment Entry')

@section('content')
@php
    $defaultDate = now()->format('d/m/Y');
    $defaultReceivedDate = now()->addDay()->format('d/m/Y');
    $defaultRemark = 'Documents dispatched for enrollment processing.';
    $defaultConsignment = 'CN-' . $enrollment->id . '-' . now()->format('Ymd');
    $defaultTrackingLink = 'https://tracking.example.com/' . $enrollment->id;
@endphp

<section class="mx-auto max-w-4xl">
    <div class="mb-5 rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-600">Step 3 of doctor enrollment</p>
        <div class="mt-1 flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
            <div>
                <h2 class="text-2xl font-bold text-slate-900">Name: {{ $enrollment->doctor_name ?? '—' }}</h2>
                <p class="mt-1 text-sm text-slate-500">Submit the post document details for this enrollment.</p>
            </div>
            <div class="rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-700">
                <span class="font-semibold text-slate-900">Customer ID:</span> {{ $enrollment->customer_id_no ?? '—' }}
            </div>
        </div>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-lg">
        <form action="{{ route('admin.posts.store') }}" method="post" enctype="multipart/form-data" id="post_upload_form" class="space-y-6">
            @csrf
            <input type="hidden" name="doctor" value="{{ $enrollment->id }}">

            <div class="grid gap-5 md:grid-cols-2">
                <div class="form-group">
                    <label class="mb-2 block text-sm font-semibold text-slate-700" for="post_doc_date">Post date <span class="text-red-500">*</span></label>
                    <input type="text" id="post_doc_date" name="post_doc_date" value="{{ old('post_doc_date', $defaultDate) }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100" autocomplete="off" required>
                </div>

                <div class="form-group">
                    <label class="mb-2 block text-sm font-semibold text-slate-700" for="post_doc_consignment_no">Consignment number <span class="text-red-500">*</span></label>
                    <input type="text" id="post_doc_consignment_no" name="post_doc_consignment_no" value="{{ old('post_doc_consignment_no', $defaultConsignment) }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100" required>
                </div>

                <div class="form-group">
                    <label class="mb-2 block text-sm font-semibold text-slate-700" for="post_doc_by">Post by <span class="text-red-500">*</span></label>
                    <input type="text" id="post_doc_by" name="post_doc_by" value="{{ old('post_doc_by', auth()->user()->name ?? 'Super Admin') }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100" required>
                </div>

                <div class="form-group">
                    <label class="mb-2 block text-sm font-semibold text-slate-700" for="post_doc_recieved_date">Received date <span class="text-red-500">*</span></label>
                    <input type="text" id="post_doc_recieved_date" name="post_doc_recieved_date" value="{{ old('post_doc_recieved_date', $defaultReceivedDate) }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100" autocomplete="off" required>
                </div>

                <div class="form-group">
                    <label class="mb-2 block text-sm font-semibold text-slate-700" for="post_doc_recieved_by">Received by</label>
                    <input type="text" id="post_doc_recieved_by" name="post_doc_recieved_by" value="{{ old('post_doc_recieved_by', $enrollment->doctor_name ?? 'Office Desk') }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                </div>

                <div class="form-group">
                    <label class="mb-2 block text-sm font-semibold text-slate-700" for="tracking_link">Tracking link</label>
                    <input type="url" id="tracking_link" name="tracking_link" value="{{ old('tracking_link', $defaultTrackingLink) }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100" placeholder="https://">
                </div>
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                <div class="form-group md:col-span-2">
                    <label class="mb-2 block text-sm font-semibold text-slate-700" for="post_doc_remark">Remark <span class="text-red-500">*</span></label>
                    <input type="text" id="post_doc_remark" name="post_doc_remark" value="{{ old('post_doc_remark', $defaultRemark) }}" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-100" required>
                </div>

                <div class="form-group md:col-span-2">
                    <label class="mb-2 block text-sm font-semibold text-slate-700" for="post_doc_file">Document file</label>
                    <input type="file" id="post_doc_file" name="post_doc_file" class="block w-full rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-blue-600 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:bg-slate-100">
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-5">
                <a href="{{ route('admin.enrollment.step2', $enrollment) }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Back</a>
                <a href="{{ route('admin.posts') }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-500">
                    <i class="ri-send-plane-line"></i>
                    <span>Submit post</span>
                </button>
            </div>
        </form>
    </div>
</section>

@push('scripts')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        if (typeof flatpickr !== 'undefined') {
            document.querySelectorAll('#post_doc_date, #post_doc_recieved_date').forEach(function (input) {
                flatpickr(input, { dateFormat: 'd/m/Y' });
            });
        }
    </script>
@endpush
@endsection

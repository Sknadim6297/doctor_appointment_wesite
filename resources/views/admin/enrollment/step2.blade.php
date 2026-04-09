@extends('admin.layouts.app')

@section('title', 'Enrollment Preview')
@section('page-title', 'Doctor Enrollment Entry')

@section('content')
@php
    $doctorName = $enrollment->doctor_name ?: 'Doctor Enrollment';
    $postDate = now()->format('d/m/Y');
@endphp

<section class="mx-auto max-w-5xl">
    <div class="mb-5 rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-600">Step 2 of doctor enrollment</p>
                <h2 class="mt-1 text-2xl font-bold text-slate-900">Name: {{ $doctorName }}</h2>
                <p class="mt-1 text-sm text-slate-500">Review the enrollment document before moving to post submission.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                    <i class="ri-download-2-line"></i>
                    <span>Download PDF</span>
                </button>
                <a href="{{ route('admin.enrollment.step3', $enrollment) }}" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-500">
                    <span>Continue</span>
                    <i class="ri-arrow-right-line"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-gradient-to-br from-white to-slate-50 shadow-lg">
        <div class="border-b border-slate-200 bg-slate-900 px-6 py-4 text-white">
            <p class="text-xs uppercase tracking-[0.2em] text-slate-300">Enrollment document preview</p>
            <h3 class="mt-1 text-xl font-semibold">Step 2 Summary</h3>
        </div>

        <div class="grid gap-6 p-6 lg:grid-cols-[1.35fr_0.65fr]">
            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="mb-4 flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 pb-4">
                    <div>
                        <p class="text-sm font-semibold text-slate-500">Customer ID</p>
                        <p class="text-lg font-bold text-slate-900">{{ $enrollment->customer_id_no ?? '—' }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-semibold text-slate-500">Money Receipt No</p>
                        <p class="text-lg font-bold text-slate-900">{{ $enrollment->money_rc_no ?? '—' }}</p>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Doctor</p>
                        <p class="mt-1 text-base font-semibold text-slate-900">{{ $enrollment->doctor_name ?? '—' }}</p>
                        <p class="mt-1 text-sm text-slate-600">{{ $enrollment->qualification ?? '—' }}</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Specialization</p>
                        <p class="mt-1 text-base font-semibold text-slate-900">{{ $enrollment->specialization?->name ?? '—' }}</p>
                        <p class="mt-1 text-sm text-slate-600">Plan: {{ $enrollment->plan_name ?? '—' }}</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Contact</p>
                        <p class="mt-1 text-base font-semibold text-slate-900">{{ $enrollment->mobile1 ?? '—' }}</p>
                        <p class="mt-1 text-sm text-slate-600">{{ $enrollment->doctor_email ?? '—' }}</p>
                    </div>
                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Address</p>
                        <p class="mt-1 text-base font-semibold text-slate-900">{{ $enrollment->city_name ?? '—' }}, {{ $enrollment->state_name ?? '—' }}</p>
                        <p class="mt-1 text-sm text-slate-600">{{ $enrollment->clinic_address ?? '—' }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5">
                <p class="text-sm font-semibold uppercase tracking-[0.14em] text-slate-500">Next action</p>
                <h4 class="mt-2 text-lg font-bold text-slate-900">Submit post document</h4>
                <p class="mt-2 text-sm leading-6 text-slate-600">Once the preview is correct, continue to Step 3 and submit the dispatched post details.</p>

                <div class="mt-5 space-y-3 rounded-2xl bg-slate-50 p-4 text-sm text-slate-700">
                    <div class="flex items-center justify-between gap-3">
                        <span class="font-medium">Preview date</span>
                        <span>{{ $postDate }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="font-medium">Bond to mail</span>
                        <span>{{ $enrollment->bond_to_mail ? 'Enabled' : 'Disabled' }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="font-medium">Auto SMS</span>
                        <span>{{ $enrollment->auto_sms_enabled ? 'Enabled' : 'Disabled' }}</span>
                    </div>
                </div>

                <a href="{{ route('admin.enrollment.step3', $enrollment) }}" class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white hover:bg-blue-500">
                    <span>Continue to Step 3</span>
                    <i class="ri-arrow-right-line"></i>
                </a>
            </div>
        </div>
    </div>
</section>

<style>
    @media print {
        .topbar,
        .side-rail,
        .btn,
        .nav-link,
        button,
        a[href*='step-3'] {
            display: none !important;
        }

        body {
            background: #fff !important;
        }

        main {
            padding: 0 !important;
        }

        section {
            max-width: none !important;
            margin: 0 !important;
        }
    }
</style>
@endsection

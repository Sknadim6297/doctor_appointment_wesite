@extends('admin.layouts.app')

@section('title', 'Pending Approvals')
@section('page-title', 'Pending Enrollment Approvals')

@section('content')
@php
    $documentReadiness = $documentReadiness ?? [];
@endphp

@if(session('success'))
    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-900">{{ session('error') }}</div>
@endif

<!-- Hero -->
<div class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-gradient-to-br from-amber-50 via-white to-blue-50 shadow-sm">
    <div class="p-6 md:p-8">
        <div class="mb-4 flex flex-wrap items-center gap-2 text-sm">
            <a href="{{ route('admin.enrollment') }}" class="inline-flex items-center gap-1 text-slate-600 hover:text-slate-900">
                <i class="ri-arrow-left-line"></i> Back to Enrollments
            </a>
            <span class="text-slate-300">|</span>
            <a href="{{ route('admin.enrollment.monitoring') }}" class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-800">
                <i class="ri-pulse-line"></i> Enrollment CRM
            </a>
        </div>

        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
            <div class="max-w-2xl">
                <h1 class="text-3xl font-bold tracking-tight text-slate-900">Pending Approvals</h1>
                <p class="mt-2 text-slate-600">Employee-submitted enrollments waiting for your decision. Open the full application, verify documents, then approve or reject.</p>
            </div>
            <div class="flex shrink-0 flex-col items-center rounded-2xl border border-amber-200 bg-white px-8 py-5 shadow-sm">
                <span class="text-4xl font-bold text-amber-600">{{ $enrollments->total() }}</span>
                <span class="text-sm font-medium text-slate-600">awaiting review</span>
            </div>
        </div>

        <!-- Workflow explainer -->
        <div class="mt-6 grid gap-4 md:grid-cols-4">
            <div class="rounded-xl border border-slate-200 bg-white/80 p-4">
                <p class="text-xs font-bold uppercase text-slate-500">Step 1</p>
                <p class="mt-1 text-sm font-semibold text-slate-900">Employee submits</p>
                <p class="mt-1 text-xs text-slate-500">Form + required documents</p>
            </div>
            <div class="rounded-xl border-2 border-amber-400 bg-amber-50 p-4">
                <p class="text-xs font-bold uppercase text-amber-800">You are here</p>
                <p class="mt-1 text-sm font-semibold text-amber-950">Admin approves</p>
                <p class="mt-1 text-xs text-amber-800">Review full dossier & decide</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white/80 p-4">
                <p class="text-xs font-bold uppercase text-slate-500">Step 2–4</p>
                <p class="mt-1 text-sm font-semibold text-slate-900">Employee continues</p>
                <p class="mt-1 text-xs text-slate-500">Preview, policy, post (after approval)</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white/80 p-4">
                <p class="text-xs font-bold uppercase text-slate-500">Complete</p>
                <p class="mt-1 text-sm font-semibold text-slate-900">Active doctor</p>
                <p class="mt-1 text-xs text-slate-500">Doctor List when finished</p>
            </div>
        </div>

        <div class="mt-4 rounded-xl border border-blue-200 bg-blue-50/80 px-4 py-3 text-sm text-blue-950">
            <p class="font-semibold">What does &ldquo;Approve&rdquo; mean?</p>
            <p class="mt-1 text-blue-900">Approval unlocks Steps 2–4 for the <strong>same employee who created</strong> the enrollment. They can add policy receipt and complete onboarding. You are recorded as the approver. Reject stops the application; Send back asks the employee to fix Step 1.</p>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="mb-6 rounded-xl border border-slate-200 bg-white shadow-sm">
    <button type="button" onclick="document.getElementById('pending-filters').classList.toggle('hidden')" class="flex w-full items-center justify-between px-5 py-4 text-left font-semibold text-slate-900">
        <span class="flex items-center gap-2"><i class="ri-filter-3-line text-lg text-slate-500"></i> Filters</span>
        <i class="ri-arrow-down-s-line text-xl text-slate-400"></i>
    </button>
    <form id="pending-filters" method="GET" action="{{ route('admin.enrollment.pending') }}" class="hidden border-t border-slate-100 p-5">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
            <div class="lg:col-span-2">
                <label class="mb-1 block text-xs font-semibold text-slate-600">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Customer ID, name, phone…">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">Submitted by</label>
                <select name="employee_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">All employees</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" @selected((string) request('employee_id') === (string) $employee->id)>{{ $employee->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">Status</label>
                <select name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="pending" @selected(request('status', 'pending') === 'pending')>Pending only</option>
                    <option value="" @selected(request('status') === '')>All statuses</option>
                    <option value="approved" @selected(request('status') === 'approved')>Approved</option>
                    <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
                </select>
            </div>
        </div>
        <div class="mt-4 flex gap-2">
            <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Apply</button>
            <a href="{{ route('admin.enrollment.pending') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset</a>
        </div>
    </form>
</div>

@if($enrollments->total() > 0)
    <div class="space-y-4">
        @foreach($enrollments as $enr)
            @php
                $status = strtolower((string) $enr->status);
                $readiness = $documentReadiness[$enr->id] ?? ['missing' => [], 'ready' => false];
                $planLabel = match ((int) ($enr->plan ?? 0)) {
                    1 => 'Normal', 2 => 'High Risk', 3 => 'Combo', default => '—',
                };
            @endphp
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-shadow hover:shadow-md">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-xl font-bold text-slate-900">{{ $enr->doctor_name }}</h2>
                            @if($status === 'pending')
                                <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-900">
                                    <i class="ri-time-line"></i> Pending
                                </span>
                            @elseif($status === 'approved')
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-800">Approved</span>
                            @elseif($status === 'rejected')
                                <span class="inline-flex items-center gap-1 rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-semibold text-rose-800">Rejected</span>
                            @endif
                            @if($readiness['ready'])
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-800 ring-1 ring-emerald-200">Docs OK</span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-medium text-rose-800 ring-1 ring-rose-200">Missing docs</span>
                            @endif
                        </div>

                        <p class="mt-1 font-mono text-sm text-slate-600">{{ $enr->customer_id_no }}</p>

                        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            <div class="rounded-lg bg-slate-50 px-3 py-2">
                                <p class="text-xs font-semibold uppercase text-slate-500">Submitted by</p>
                                <p class="mt-0.5 font-semibold text-slate-900">{{ $enr->creator?->name ?? 'Unknown' }}</p>
                                <p class="text-xs text-slate-500">{{ $enr->created_by_role ?? $enr->creator?->role ?? 'employee' }}
                                    @if($enr->creator?->phone) · {{ $enr->creator->phone }} @endif
                                </p>
                            </div>
                            <div class="rounded-lg bg-slate-50 px-3 py-2">
                                <p class="text-xs font-semibold uppercase text-slate-500">Submitted on</p>
                                <p class="mt-0.5 font-semibold text-slate-900">{{ optional($enr->created_at)->format('d M Y') }}</p>
                                <p class="text-xs text-slate-500">{{ optional($enr->created_at)->format('h:i A') }} · {{ optional($enr->created_at)->diffForHumans() }}</p>
                            </div>
                            <div class="rounded-lg bg-slate-50 px-3 py-2">
                                <p class="text-xs font-semibold uppercase text-slate-500">Plan / contact</p>
                                <p class="mt-0.5 font-semibold text-slate-900">{{ $planLabel }} · {{ $enr->specialization?->name ?? '—' }}</p>
                                <p class="text-xs text-slate-500">{{ $enr->mobile1 ?? '—' }}</p>
                            </div>
                        </div>

                        @if(!$readiness['ready'] && !empty($readiness['missing']))
                            <p class="mt-3 text-xs text-rose-700"><strong>Cannot approve until uploaded:</strong> {{ implode(', ', $readiness['missing']) }}</p>
                        @endif

                        @if($status === 'approved' && $enr->approver)
                            <p class="mt-3 text-xs text-emerald-800">Approved by <strong>{{ $enr->approver->name }}</strong> · {{ optional($enr->approved_at)->format('d M Y, h:i A') }}</p>
                        @endif
                    </div>

                    <div class="flex flex-col gap-2 sm:flex-row lg:flex-col lg:items-stretch lg:min-w-[200px]">
                        <a href="{{ route('admin.enrollment.details', $enr->id) }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white hover:bg-blue-700">
                            <i class="ri-file-search-line text-lg"></i>
                            Review full application
                        </a>
                        @if($status === 'pending' && ($enr->workflow_status ?? '') !== 'returned_for_correction')
                            @if($canApprove && $readiness['ready'])
                                <form action="{{ route('admin.enrollment.approve', $enr->id) }}" method="POST" onsubmit="return confirm('Approve this enrollment? The submitting employee will be able to continue Steps 2–4.');">
                                    @csrf
                                    <button type="submit" class="w-full rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-800 hover:bg-emerald-100">
                                        Quick approve
                                    </button>
                                </form>
                            @elseif($canApprove && !$readiness['ready'])
                                <span class="block rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-center text-xs text-slate-500">Open application to review missing documents</span>
                            @endif
                        @endif
                    </div>
                </div>
            </article>
        @endforeach
    </div>

    @if($enrollments->hasPages())
        <div class="mt-6">{{ $enrollments->links() }}</div>
    @endif
@else
    <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100">
            <i class="ri-checkbox-circle-line text-3xl text-emerald-600"></i>
        </div>
        <h2 class="mt-4 text-xl font-bold text-slate-900">No pending applications</h2>
        <p class="mx-auto mt-2 max-w-md text-sm text-slate-600">Every enrollment in the queue has been reviewed. New submissions from employees will appear here automatically.</p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ route('admin.enrollment.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                <i class="ri-add-line"></i> New enrollment (admin)
            </a>
            <a href="{{ route('admin.enrollment.monitoring', 'new_entries') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                View new entries
            </a>
        </div>
    </div>
@endif
@endsection

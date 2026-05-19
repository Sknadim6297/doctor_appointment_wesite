@extends('admin.layouts.app')

@section('title', 'Enrollment CRM')
@section('page-title', 'Enrollment Monitoring & Approvals')

@section('content')
@php
    $tabs = [
        'overview' => ['label' => 'Overview', 'icon' => 'ri-layout-grid-line'],
        'new_entries' => ['label' => 'New entries', 'icon' => 'ri-sparkling-line'],
        'pending_approvals' => ['label' => 'Pending approvals', 'icon' => 'ri-time-line'],
        'incomplete' => ['label' => 'Incomplete', 'icon' => 'ri-draft-line'],
        'completed' => ['label' => 'Completed', 'icon' => 'ri-checkbox-circle-line'],
        'rejected' => ['label' => 'Rejected', 'icon' => 'ri-close-circle-line'],
        'returned' => ['label' => 'Returned for correction', 'icon' => 'ri-arrow-go-back-line'],
    ];
@endphp

<div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div>
        <div class="mb-2 flex flex-wrap items-center gap-2 text-sm">
            <a href="{{ route('admin.dashboard') }}" class="text-slate-600 hover:text-slate-900">Dashboard</a>
            <span class="text-slate-300">/</span>
            <span class="font-semibold text-slate-900">Enrollment CRM</span>
        </div>
        <h1 class="text-3xl font-bold text-slate-900">Enrollment pipeline</h1>
        <p class="mt-1 max-w-2xl text-sm text-slate-600">Monitor every doctor enrollment from draft through approval and completion, with full workflow visibility.</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admin.enrollment.pending') }}" class="inline-flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-900 hover:bg-amber-100">
            <i class="ri-shield-check-line"></i>
            Approval queue
        </a>
        <a href="{{ route('admin.doctors.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">
            <i class="ri-list-check-2"></i>
            Active doctor list
        </a>
    </div>
</div>

<div class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
    @foreach([
        ['key' => 'new_entries', 'label' => 'New entries', 'tone' => 'from-slate-700 to-slate-900'],
        ['key' => 'pending_approvals', 'label' => 'Pending approvals', 'tone' => 'from-amber-500 to-orange-600'],
        ['key' => 'incomplete', 'label' => 'Incomplete', 'tone' => 'from-rose-500 to-rose-700'],
        ['key' => 'completed', 'label' => 'Completed', 'tone' => 'from-emerald-500 to-teal-600'],
        ['key' => 'rejected', 'label' => 'Rejected', 'tone' => 'from-slate-500 to-slate-700'],
        ['key' => 'returned', 'label' => 'Returned', 'tone' => 'from-violet-500 to-indigo-600'],
    ] as $card)
        <a href="{{ route('admin.enrollment.monitoring', ['bucket' => $card['key']]) }}"
           class="rounded-2xl bg-gradient-to-br p-[1px] shadow-sm ring-1 ring-black/5 transition hover:shadow-md {{ $bucket === $card['key'] ? 'ring-2 ring-blue-400' : '' }}">
            <div class="h-full rounded-2xl bg-white p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $card['label'] }}</p>
                <p class="mt-2 bg-gradient-to-r bg-clip-text text-3xl font-extrabold text-transparent {{ $card['tone'] }}">{{ number_format($counts[$card['key']] ?? 0) }}</p>
            </div>
        </a>
    @endforeach
</div>

<div class="mb-4 flex flex-wrap gap-2 border-b border-slate-200 pb-4">
    @foreach($tabs as $key => $meta)
        <a href="{{ route('admin.enrollment.monitoring', ['bucket' => $key]) }}"
           class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold transition
           {{ $bucket === $key ? 'bg-slate-900 text-white shadow' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
            <i class="{{ $meta['icon'] }}"></i>
            {{ $meta['label'] }}
        </a>
    @endforeach
</div>

<div class="mb-6 rounded-2xl border border-slate-200 bg-white shadow-sm">
    <form method="GET" action="{{ route('admin.enrollment.monitoring', ['bucket' => $bucket]) }}" class="border-b border-slate-100 p-4">
        <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-4">
            <div class="lg:col-span-2">
                <label class="mb-1 block text-xs font-semibold text-slate-600">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="Name, customer ID, phone…">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
            </div>
        </div>
        <div class="mt-3 flex flex-wrap gap-2">
            <button type="submit" class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500">Apply</button>
            <a href="{{ route('admin.enrollment.monitoring', ['bucket' => $bucket]) }}" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset</a>
        </div>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[900px] text-left text-sm">
            <thead class="border-b border-slate-100 bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Doctor</th>
                    <th class="px-4 py-3">Customer ID</th>
                    <th class="px-4 py-3">Current step</th>
                    <th class="px-4 py-3">Approval status</th>
                    <th class="px-4 py-3">Workflow</th>
                    <th class="px-4 py-3">Last updated</th>
                    <th class="px-4 py-3">Created by</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($enrollments as $enr)
                    <tr class="hover:bg-slate-50/80">
                        <td class="px-4 py-3 font-semibold text-slate-900">{{ $enr->doctor_name ?: '—' }}</td>
                        <td class="px-4 py-3 font-mono text-slate-700">{{ $enr->customer_id_no ?: '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-800">Step {{ (int) ($enr->current_step ?? 1) }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ \App\Support\EnrollmentWorkflow::approvalBadgeClasses($enr) }}">
                                {{ \App\Support\EnrollmentWorkflow::approvalStatus($enr) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ \App\Support\EnrollmentWorkflow::badgeClasses($enr->workflow_status) }}">
                                {{ \App\Support\EnrollmentWorkflow::displayStatus($enr) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ optional($enr->last_activity_at ?? $enr->updated_at)->format('d M Y, h:i A') }}</td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-slate-800">{{ $enr->creator?->name ?? '—' }}</div>
                            <div class="text-xs text-slate-500">{{ $enr->created_by_role ?? $enr->creator?->role ?? '' }}</div>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="inline-flex flex-wrap justify-end gap-2">
                                @php
                                    $rowWorkflow = \App\Support\EnrollmentWorkflow::normalize($enr->workflow_status);
                                @endphp
                                <a href="{{ route('admin.enrollment.details', $enr->id) }}" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-800 hover:bg-slate-50">{{ $rowWorkflow === \App\Support\EnrollmentWorkflow::COMPLETED ? 'View' : 'Dossier' }}</a>
                                @php $isRowCreator = auth()->id() === (int) ($enr->created_by ?? 0); @endphp
                                @if(\App\Support\EnrollmentWorkflow::canResumeFromDashboard($enr) && (($canEdit ?? false) || $isRowCreator))
                                    <a href="{{ route('admin.enrollment.resume', $enr) }}" class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-500">
                                        {{ \App\Support\EnrollmentWorkflow::dashboardResumeLabel($enr) }}
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-10 text-center text-slate-500">No enrollments in this view.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($enrollments->hasPages())
        <div class="border-t border-slate-100 px-4 py-3">{{ $enrollments->links() }}</div>
    @endif
</div>
@endsection

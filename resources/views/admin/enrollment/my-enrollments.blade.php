@extends('admin.layouts.app')

@section('title', 'My Enrollments')
@section('page-title', 'My Enrollments')

@section('content')
<div class="mb-6 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
    <div>
        <h1 class="text-3xl font-bold text-slate-900">My Enrollments</h1>
        <p class="mt-1 text-slate-600">Track only the enrollments submitted from your account.</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admin.enrollment.create') }}" class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-500">New enrollment</a>
    </div>
</div>

@if(!empty($employeeStats))
<div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
    @foreach([
        ['k' => 'draft', 'label' => 'Drafts'],
        ['k' => 'pending', 'label' => 'Pending'],
        ['k' => 'approved', 'label' => 'Approved'],
        ['k' => 'rejected', 'label' => 'Rejected'],
        ['k' => 'incomplete', 'label' => 'In progress'],
    ] as $stat)
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-semibold uppercase text-slate-500">{{ $stat['label'] }}</p>
            <p class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($employeeStats[$stat['k']] ?? 0) }}</p>
        </div>
    @endforeach
</div>
@endif

<div class="mb-6 rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 bg-gradient-to-r from-slate-50 to-white px-6 py-4">
        <h3 class="font-semibold text-slate-900">Search</h3>
    </div>
    <form method="GET" action="{{ route('admin.my-enrollments.index') }}" class="grid gap-4 p-6 md:grid-cols-4">
        <div class="md:col-span-2">
            <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="ID, name, phone..." class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
        </div>
        <div>
            <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Status</label>
            <select name="status" class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                <option value="" {{ request('status') === '' ? 'selected' : '' }}>All</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
        </div>
        <div class="flex items-end gap-3">
            <button type="submit" class="flex-1 rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white hover:bg-blue-700">Search</button>
            <a href="{{ route('admin.my-enrollments.index') }}" class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset</a>
        </div>
    </form>
</div>

<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="border-b border-slate-200 bg-gradient-to-r from-slate-50 to-white">
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Customer ID</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Proposer</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Step</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">Status</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-slate-600">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($enrollments as $enr)
                    <tr class="transition-colors hover:bg-blue-50">
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $enr->customer_id_no }}</td>
                        <td class="px-4 py-3">
                            <div class="text-sm text-slate-900">{{ $enr->doctor_name }}</div>
                            <div class="text-xs text-slate-500">{{ $enr->mobile1 ?? '—' }}</div>
                            @if($enr->status === 'rejected' && $enr->rejection_reason)
                                <p class="mt-1 text-xs text-rose-700">{{ Str::limit($enr->rejection_reason, 80) }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm font-medium text-slate-800">Step {{ (int) ($enr->current_step ?? 1) }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ \App\Support\EnrollmentWorkflow::dashboardBadgeClasses($enr) }}">
                                {{ \App\Support\EnrollmentWorkflow::dashboardStatusLabel($enr) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <a href="{{ route('admin.my-enrollments.show', $enr->id) }}" class="inline-flex rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">No enrollments found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($enrollments->hasPages())
        <div class="border-t border-slate-200 bg-slate-50 px-4 py-4">{{ $enrollments->links() }}</div>
    @endif
</div>
@endsection

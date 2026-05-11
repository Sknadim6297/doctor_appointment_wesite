@extends('admin.layouts.app')

@section('title', 'Pending Approvals')
@section('page-title', 'Pending Enrollment Approvals')

@section('content')
<!-- Header Section -->
<div class="mb-6 flex items-center justify-between">
    <div>
        <div class="mb-2 flex items-center gap-2">
            <a href="{{ route('admin.enrollment') }}" class="inline-flex items-center gap-1 text-sm text-slate-600 hover:text-slate-900">
                <i class="ri-arrow-left-line text-lg"></i>
                Back to Enrollments
            </a>
        </div>
        <h1 class="text-3xl font-bold text-slate-900">Pending Approvals</h1>
        <p class="mt-1 text-slate-600">Review and approve pending enrollment applications</p>
    </div>
    <div class="flex flex-col items-end gap-1">
        <div class="text-4xl font-bold text-blue-600">{{ $enrollments->total() }}</div>
        <div class="text-sm text-slate-600">Pending to Review</div>
    </div>
</div>

<!-- Filter Card -->
<div class="mb-6 rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 bg-gradient-to-r from-slate-50 to-white px-6 py-4">
        <h3 class="font-semibold text-slate-900 flex items-center gap-2">
            <i class="ri-filter-line text-lg text-slate-600"></i>
            Advanced Filters
        </h3>
    </div>
    
    <form method="GET" action="{{ route('admin.enrollment.pending') }}" class="p-6">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-6">
            <!-- Search -->
            <div class="lg:col-span-2">
                <label class="block text-xs font-semibold text-slate-700 mb-2">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500" placeholder="ID, Customer, Phone...">
            </div>

            <!-- Date From -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-2">From Date</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            </div>

            <!-- Date To -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-2">To Date</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
            </div>

            <!-- Status -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-2">Status</label>
                <select name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    <option value="">All Status</option>
                    <option value="pending" {{ request('status', 'pending') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>

            <!-- Employee -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-2">Employee</label>
                <select name="employee_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    <option value="">All Employees</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" {{ (string) request('employee_id') === (string) $employee->id ? 'selected' : '' }}>
                            {{ $employee->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-4">
            <!-- Month -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-2">Month</label>
                <select name="search_month" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    <option value="">All Months</option>
                    @php $months = ['January','February','March','April','May','June','July','August','September','October','November','December']; @endphp
                    @foreach($months as $month)
                        <option value="{{ $month }}" {{ request('search_month') === $month ? 'selected' : '' }}>{{ $month }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Year -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-2">Year</label>
                <select name="search_year" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    <option value="">All Years</option>
                    @foreach(range((int) date('Y') + 1, 2006) as $year)
                        <option value="{{ $year }}" {{ (string) request('search_year') === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Button Group -->
            <div class="flex items-end gap-2 lg:col-span-2">
                <button type="submit" class="flex-1 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 transition-colors flex items-center justify-center gap-2">
                    <i class="ri-search-line text-lg"></i>
                    Apply Filters
                </button>
                <a href="{{ route('admin.enrollment.pending') }}" class="flex-1 rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                    Reset
                </a>
            </div>
        </div>
    </form>
</div>

<!-- Table Section -->
@if($enrollments->total() > 0)
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-slate-200 bg-gradient-to-r from-slate-50 to-white">
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Customer ID</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Proposer</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Agent</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Created By</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Submitted Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Status</th>
                        @if(($canEdit ?? false) || ($canApprove ?? false) || ($canReject ?? false))
                            <th class="px-4 py-3 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($enrollments as $enr)
                        <tr class="hover:bg-blue-50 transition-colors">
                            <!-- Customer ID -->
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-900">{{ $enr->customer_id_no }}</div>
                            </td>

                            <!-- Proposer Name -->
                            <td class="px-4 py-3">
                                <div class="text-sm text-slate-900">{{ $enr->doctor_name }}</div>
                            </td>

                            <!-- Agent Name -->
                            <td class="px-4 py-3">
                                <div class="text-sm text-slate-700">{{ $enr->agent_name }}</div>
                            </td>

                            <!-- Created By -->
                            <td class="px-4 py-3">
                                <div class="text-sm">
                                    <div class="font-medium text-slate-900">{{ $enr->creator?->name ?? 'N/A' }}</div>
                                    <div class="text-xs text-slate-500 mt-0.5">{{ $enr->created_by_role ?? $enr->creator?->role ?? 'N/A' }}</div>
                                </div>
                            </td>

                            <!-- Submitted Date -->
                            <td class="px-4 py-3">
                                <div class="text-sm text-slate-700">{{ optional($enr->created_at)->format('d M Y') }}</div>
                                <div class="text-xs text-slate-500">{{ optional($enr->created_at)->format('H:i') }}</div>
                            </td>

                            <!-- Status -->
                            <td class="px-4 py-3">
                                @php $status = strtolower((string) $enr->status); @endphp
                                @if($status === 'pending')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">
                                        <i class="ri-time-line text-xs"></i>
                                        Pending
                                    </span>
                                @elseif($status === 'approved')
                                    <div>
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">
                                            <i class="ri-check-circle-line text-xs"></i>
                                            Approved
                                        </span>
                                        @if(optional($enr->approver)->name)
                                            <div class="mt-1 text-2xs text-slate-500">By {{ optional($enr->approver)->name }} • {{ optional($enr->approved_at)->format('d M Y H:i') }}</div>
                                        @endif
                                    </div>
                                @elseif($status === 'rejected')
                                    <div>
                                        <span class="inline-flex items-center gap-1 rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold text-rose-800">
                                            <i class="ri-close-circle-line text-xs"></i>
                                            Rejected
                                        </span>
                                        @if(optional($enr->approver)->name)
                                            <div class="mt-1 text-2xs text-slate-500">By {{ optional($enr->approver)->name }} • {{ optional($enr->updated_at ?? $enr->approved_at)->format('d M Y H:i') }}</div>
                                        @endif
                                    </div>
                                @else
                                    <span class="inline-block rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ ucfirst($status) }}</span>
                                @endif
                            </td>

                            @if(($canEdit ?? false) || ($canApprove ?? false) || ($canReject ?? false))
                                <!-- Actions -->
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-2">
                                    <!-- View Details -->
                                    <a href="{{ route('admin.enrollment.details', $enr->id) }}" class="inline-flex items-center gap-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 transition-colors" title="View Details">
                                        <i class="ri-eye-line text-base"></i>
                                    </a>

                                    @if($status === 'pending')
                                        @php
                                            $isCreator = Auth::id() && (Auth::id() === $enr->created_by);
                                        @endphp

                                            @if(($canEdit ?? false) || $isCreator)
                                                <a href="{{ route('admin.enrollment.edit', $enr->id) }}" class="inline-flex items-center gap-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 transition-colors" title="Edit">
                                                    <i class="ri-edit-line text-base"></i>
                                                    Edit
                                                </a>
                                            @endif
                                            @if($canApprove ?? false)
                                            <form action="{{ route('admin.enrollment.approve', $enr->id) }}" method="POST" class="inline" onclick="return confirm('Approve this enrollment?')">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-medium text-white hover:bg-emerald-700 transition-colors" title="Approve">
                                                    <i class="ri-check-line text-base"></i>
                                                    Approve
                                                </button>
                                            </form>
                                        @endif
                                        @if($canReject ?? false)
                                            <form action="{{ route('admin.enrollment.reject', $enr->id) }}" method="POST" class="inline" onclick="return confirm('Reject this enrollment?')">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center gap-1 rounded-lg bg-rose-600 px-3 py-2 text-xs font-medium text-white hover:bg-rose-700 transition-colors" title="Reject">
                                                    <i class="ri-close-line text-base"></i>
                                                    Reject
                                                </button>
                                            </form>
                                        @endif

                                        @if(!$canApprove && !$canReject)
                                            <span class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-500">
                                                —
                                            </span>
                                        @endif
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium text-slate-500">
                                            —
                                        </span>
                                    @endif
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($enrollments->hasPages())
            <div class="border-t border-slate-200 bg-slate-50 px-4 py-4">
                <div class="flex items-center justify-between">
                    <div class="text-xs text-slate-600">
                        Showing <span class="font-semibold">{{ $enrollments->firstItem() }}</span> to <span class="font-semibold">{{ $enrollments->lastItem() }}</span> of <span class="font-semibold">{{ $enrollments->total() }}</span> results
                    </div>
                    <div>{{ $enrollments->links() }}</div>
                </div>
            </div>
        @endif
    </div>
@else
    <!-- Empty State -->
    <div class="rounded-xl border border-slate-200 bg-white">
        <!-- Empty Content -->
        <div class="px-6 py-12 sm:px-12">
            <div class="mx-auto max-w-sm text-center">
                <!-- Icon -->
                <div class="mb-6 flex justify-center">
                    <div class="rounded-full bg-blue-100 p-3">
                        <i class="ri-check-double-line text-3xl text-blue-600"></i>
                    </div>
                </div>

                <!-- Content -->
                <h2 class="text-xl font-bold text-slate-900">No Pending Approvals</h2>
                <p class="mt-2 text-sm text-slate-600">All enrollment applications have been reviewed. Great job staying on top of things!</p>

                <!-- Action Buttons -->
                <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-center">
                    <a href="{{ route('admin.enrollment.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 transition-colors">
                        <i class="ri-add-line text-lg"></i>
                        New Enrollment
                    </a>
                    <a href="{{ route('admin.enrollment.pending') }}" class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                        <i class="ri-refresh-line text-lg"></i>
                        Refresh
                    </a>
                </div>
            </div>
        </div>
    </div>
@endif

@endsection

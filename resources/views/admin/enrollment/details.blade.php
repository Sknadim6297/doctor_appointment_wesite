@extends('admin.layouts.app')

@section('title', 'Enrollment Details')
@section('page-title', 'Enrollment Details')

@section('content')
@php
    $na = 'N/A';
    $status = strtolower((string) ($enrollment->status ?? 'pending'));
    $planLabel = match ((int) ($enrollment->plan ?? 0)) {
        1 => 'Normal',
        2 => 'High Risk',
        3 => 'Combo',
        default => $na,
    };
    $paymentMethodLabel = match ((int) ($enrollment->payment_method ?? 0)) {
        1 => 'Cheque',
        2 => 'Cash',
        3 => 'UPI',
        default => $na,
    };
    $qualificationYears = is_array($enrollment->qualification_year) ? implode(', ', array_filter($enrollment->qualification_year)) : (string) ($enrollment->qualification_year ?? '');
    $creator = $enrollment->creator;
    $approver = $enrollment->approver;
    $currentUser = auth()->user();
    $isPrivilegedAdmin = $currentUser && (
        in_array(($currentUser->role ?? null), ['admin', 'super_admin'], true) ||
        (method_exists($currentUser, 'hasAdminRole') && $currentUser->hasAdminRole(['admin', 'super_admin']))
    );
    $canProceedToStep2 = $status === 'approved' && (
        (auth()->id() === (int) $enrollment->created_by) || $isPrivilegedAdmin
    );
    $backRoute = $isPrivilegedAdmin ? route('admin.enrollment.pending') : route('admin.enrollment');
    $backLabel = $isPrivilegedAdmin ? 'Pending Approvals' : 'My Enrollments';
@endphp

<!-- Header Navigation -->
<div class="mb-6 flex items-center gap-3">
    <a href="{{ $backRoute }}" class="btn btn-ghost">
        ← Back to {{ $backLabel }}
    </a>
    @if($isPrivilegedAdmin)
        <a href="{{ route('admin.enrollment.edit', $enrollment->id) }}" class="btn btn-primary">
            Edit Enrollment
        </a>
    @endif
</div>

<!-- Main Header Card -->
<div class="mb-6 rounded-3xl border border-slate-200 bg-gradient-to-br from-slate-50 to-slate-100 p-6 shadow-sm">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">{{ $enrollment->doctor_name ?? $na }}</h1>
            <p class="mt-2 text-sm text-slate-600">Customer ID: <strong>{{ $enrollment->customer_id_no }}</strong></p>
            <p class="text-sm text-slate-600">Submitted on <strong>{{ optional($enrollment->created_at)->format('d M Y \a\t h:i A') ?? $na }}</strong></p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            @if($status === 'pending')
                <span class="inline-flex items-center gap-2 rounded-full bg-amber-100 px-4 py-2 text-sm font-semibold text-amber-700">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.707-11.414a1 1 0 00-1.414-1.414L9 9.172 7.707 7.879a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    Pending Approval
                </span>
            @elseif($status === 'approved')
                <span class="inline-flex items-center gap-2 rounded-full bg-emerald-100 px-4 py-2 text-sm font-semibold text-emerald-700">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    Approved
                </span>
            @elseif($status === 'rejected')
                <span class="inline-flex items-center gap-2 rounded-full bg-rose-100 px-4 py-2 text-sm font-semibold text-rose-700">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                    Rejected
                </span>
            @endif
        </div>
    </div>

    @if($status === 'pending')
        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <strong>⚠️ Waiting for Approval:</strong> This enrollment is awaiting admin review and is currently locked. Step 2 is unavailable until it is approved.
        </div>
    @elseif($status === 'approved')
        <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            <strong>Admin has approved your enrollment.</strong> You can proceed to the next step.
        </div>
    @elseif($status === 'rejected')
        <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <strong>Rejected:</strong> This enrollment was rejected and is locked. Review the rejection reason below.
        </div>
    @endif
</div>

<!-- Quick Summary Cards -->
<div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-xs">
        <p class="text-xs font-semibold uppercase text-slate-500">Submitted By</p>
        <p class="mt-2 text-lg font-semibold text-slate-900">{{ $creator?->name ?? $na }}</p>
        <p class="mt-1 text-xs text-slate-600">{{ $enrollment->created_by_role ?: ($creator?->role ?? $na) }}</p>
    </div>
    <div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-xs">
        <p class="text-xs font-semibold uppercase text-slate-500">Plan & Coverage</p>
        <p class="mt-2 text-lg font-semibold text-slate-900">{{ $planLabel }}</p>
        <p class="mt-1 text-xs text-slate-600">{{ $enrollment->specialization?->name ?: $na }}</p>
    </div>
    <div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-xs">
        <p class="text-xs font-semibold uppercase text-slate-500">Amount</p>
        <p class="mt-2 text-lg font-semibold text-emerald-600">₹ {{ number_format((float) ($enrollment->total_amount ?? 0), 2) }}</p>
        <p class="mt-1 text-xs text-slate-600">{{ $enrollment->payment_mode ?: $na }}</p>
    </div>
    <div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-xs">
        <p class="text-xs font-semibold uppercase text-slate-500">Status</p>
        <p class="mt-2 text-lg font-semibold text-slate-900">{{ ucfirst($status) }}</p>
        @if($enrollment->approved_by)
            <p class="mt-1 text-xs text-slate-600">by {{ $approver?->name ?? 'Unknown' }}</p>
        @endif
    </div>
</div>

<!-- Expandable Sections -->
<div class="space-y-4">
    <!-- Section 1: Workflow & Creator Info -->
    <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <button onclick="this.parentElement.querySelector('[data-section]').classList.toggle('hidden')" class="w-full px-6 py-4 flex items-center justify-between hover:bg-slate-50 transition-colors">
            <h3 class="text-lg font-semibold text-slate-900">Workflow & Creator Information</h3>
        </button>
        <div data-section class="border-t border-slate-200 px-6 py-4">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Created By</p>
                    <p class="mt-1 text-base font-semibold text-slate-900">{{ $creator?->name ?? $na }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Creator Role</p>
                    <p class="mt-1 text-base font-semibold text-slate-900">{{ ucfirst($enrollment->created_by_role ?: ($creator?->role ?? $na)) }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Creator Email</p>
                    <p class="mt-1 text-sm text-slate-700">{{ $creator?->email ?? $na }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Creator Mobile</p>
                    <p class="mt-1 text-sm text-slate-700">{{ $creator?->phone ?? $na }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Created At</p>
                    <p class="mt-1 text-sm text-slate-700">{{ optional($enrollment->created_at)->format('d M Y h:i A') ?? $na }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Created From</p>
                    <p class="mt-1 text-sm text-slate-700">{{ $latestActivity?->ip_address ?? $na }}</p>
                    @if(!empty($latestActivity?->user_agent))
                        <p class="mt-1 truncate text-xs text-slate-600">{{ $latestActivity->user_agent }}</p>
                    @endif
                </div>
                @if($enrollment->approved_by)
                    <div>
                        <p class="text-xs font-semibold uppercase text-slate-500">Approved By</p>
                        <p class="mt-1 text-base font-semibold text-emerald-600">{{ $approver?->name ?? $na }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase text-slate-500">Approved At</p>
                        <p class="mt-1 text-sm text-slate-700">{{ optional($enrollment->approved_at)->format('d M Y h:i A') ?? $na }}</p>
                    </div>
                @endif
                @if($enrollment->approval_remarks)
                    <div class="md:col-span-2 lg:col-span-3">
                        <p class="text-xs font-semibold uppercase text-slate-500">Approval Remarks</p>
                        <p class="mt-1 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-900">{{ $enrollment->approval_remarks }}</p>
                    </div>
                @endif
                @if($enrollment->rejection_reason)
                    <div class="md:col-span-2 lg:col-span-3">
                        <p class="text-xs font-semibold uppercase text-slate-500">Rejection Reason</p>
                        <p class="mt-1 rounded-lg bg-rose-50 p-3 text-sm text-rose-900">{{ $enrollment->rejection_reason }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Section 2: Official Details -->
    <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <button onclick="this.parentElement.querySelector('[data-section]').classList.toggle('hidden')" class="w-full px-6 py-4 flex items-center justify-between hover:bg-slate-50 transition-colors">
            <h3 class="text-lg font-semibold text-slate-900">Official Details</h3>
        </button>
        <div data-section class="hidden border-t border-slate-200 px-6 py-4">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Customer ID No</p>
                    <p class="mt-1 text-base font-mono font-semibold text-slate-900">{{ $enrollment->customer_id_no ?: $na }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Money Receipt No</p>
                    <p class="mt-1 text-base font-mono font-semibold text-slate-900">{{ $enrollment->money_rc_no ?: $na }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Broker / Agent Name</p>
                    <p class="mt-1 text-base font-semibold text-slate-900">{{ $enrollment->agent_name ?: $na }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Agent Phone No</p>
                    <p class="mt-1 text-base font-semibold text-slate-900">{{ $enrollment->agent_phone_no ?: $na }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 3: Uploaded Files -->
    <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <button onclick="this.parentElement.querySelector('[data-section]').classList.toggle('hidden')" class="w-full px-6 py-4 flex items-center justify-between hover:bg-slate-50 transition-colors">
            <h3 class="text-lg font-semibold text-slate-900">Uploaded Files & Documents</h3>
        </button>
        <div data-section class="hidden border-t border-slate-200 px-6 py-4">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <!-- Policy Receipts -->
                <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                    <div class="mb-3 flex items-center gap-2">
                        <svg class="h-5 w-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 012-2h6a2 2 0 012 2v12a1 1 0 11-2 0V7h-4v9a1 1 0 11-2 0V4z"/></svg>
                        <p class="font-semibold text-blue-900">Policy Receipts</p>
                    </div>
                    @if($enrollment->policyReceipts->isNotEmpty())
                        <ul class="space-y-2">
                            @foreach($enrollment->policyReceipts as $policyReceipt)
                                <li class="rounded border border-slate-200 bg-white p-3 text-sm">
                                    <div class="font-semibold text-slate-900">{{ $policyReceipt->policy_no ?: $na }}</div>
                                    <div class="mt-1 text-xs text-slate-600">{{ optional($policyReceipt->receive_date)->format('d M Y') ?: $na }}</div>
                                    @if($policyReceipt->policy_file)
                                        <a class="mt-2 inline-block rounded bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-700 hover:bg-blue-200" href="{{ asset('storage/' . $policyReceipt->policy_file) }}" target="_blank">📥 Download</a>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm text-blue-700">No receipts uploaded.</p>
                    @endif
                </div>

                <!-- Doctor Documents -->
                <div class="rounded-lg border border-purple-200 bg-purple-50 p-4">
                    <div class="mb-3 flex items-center gap-2">
                        <svg class="h-5 w-5 text-purple-600" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 012-2h6a2 2 0 012 2v12a1 1 0 11-2 0V7h-4v9a1 1 0 11-2 0V4z"/></svg>
                        <p class="font-semibold text-purple-900">Doctor Documents</p>
                    </div>
                    @if($enrollment->doctorDocuments->isNotEmpty())
                        <ul class="space-y-2">
                            @foreach($enrollment->doctorDocuments as $document)
                                <li class="rounded border border-slate-200 bg-white p-3 text-sm">
                                    <div class="font-semibold text-slate-900">{{ $document->document_title ?: $na }}</div>
                                    <div class="mt-1 text-xs text-slate-600">{{ $document->document_type ?: $na }}</div>
                                    @if($document->document_file)
                                        <a class="mt-2 inline-block rounded bg-purple-100 px-2 py-1 text-xs font-semibold text-purple-700 hover:bg-purple-200" href="{{ asset('storage/' . $document->document_file) }}" target="_blank">📥 Download</a>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm text-purple-700">No documents uploaded.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Section 4: Proposer Details -->
    <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <button onclick="this.parentElement.querySelector('[data-section]').classList.toggle('hidden')" class="w-full px-6 py-4 flex items-center justify-between hover:bg-slate-50 transition-colors">
            <h3 class="text-lg font-semibold text-slate-900">Proposer's Personal Details</h3>
        </button>
        <div data-section class="hidden border-t border-slate-200 px-6 py-4">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Full Name</p>
                    <p class="mt-1 text-base font-semibold text-slate-900">{{ $enrollment->doctor_name ?: $na }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Date of Birth</p>
                    <p class="mt-1 text-base font-semibold text-slate-900">{{ optional($enrollment->dob)->format('d/m/Y') ?: $na }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Email</p>
                    <p class="mt-1 text-sm text-slate-700">{{ $enrollment->doctor_email ?: $na }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Mobile 1</p>
                    <p class="mt-1 text-base font-semibold text-slate-900">{{ $enrollment->mobile1 ?: $na }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Mobile 2</p>
                    <p class="mt-1 text-base font-semibold text-slate-900">{{ $enrollment->mobile2 ?: $na }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Qualification</p>
                    <p class="mt-1 text-base font-semibold text-slate-900">{{ $enrollment->qualification ?: $na }}</p>
                </div>
                @if($qualificationYears !== '')
                    <div>
                        <p class="text-xs font-semibold uppercase text-slate-500">Qualification Years</p>
                        <p class="mt-1 text-base font-semibold text-slate-900">{{ $qualificationYears }}</p>
                    </div>
                @endif
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Medical Reg No</p>
                    <p class="mt-1 text-base font-semibold text-slate-900">{{ $enrollment->medical_registration_no ?: $na }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Year of Registration</p>
                    <p class="mt-1 text-base font-semibold text-slate-900">{{ $enrollment->year_of_reg ?: $na }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Aadhaar Card</p>
                    <p class="mt-1 font-mono text-slate-900">{{ $enrollment->aadhar_card_no ?: $na }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">PAN Card</p>
                    <p class="mt-1 font-mono text-slate-900">{{ $enrollment->pan_card_no ?: $na }}</p>
                </div>
                <div class="md:col-span-2 lg:col-span-3">
                    <p class="text-xs font-semibold uppercase text-slate-500">Address</p>
                    <p class="mt-1 text-slate-900">{{ $enrollment->doctor_address ?: $na }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Country</p>
                    <p class="mt-1 text-slate-900">{{ $enrollment->country_name ?: $na }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">State</p>
                    <p class="mt-1 text-slate-900">{{ $enrollment->state_name ?: $na }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">City</p>
                    <p class="mt-1 text-slate-900">{{ $enrollment->city_name ?: $na }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Postcode</p>
                    <p class="mt-1 text-slate-900">{{ $enrollment->postcode ?: $na }}</p>
                </div>
                <div class="md:col-span-2 lg:col-span-3">
                    <p class="text-xs font-semibold uppercase text-slate-500">Clinic Address</p>
                    <p class="mt-1 text-slate-900">{{ $enrollment->clinic_address ?: $na }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 5: Payment Details -->
    <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <button onclick="this.parentElement.querySelector('[data-section]').classList.toggle('hidden')" class="w-full px-6 py-4 flex items-center justify-between hover:bg-slate-50 transition-colors">
            <h3 class="text-lg font-semibold text-slate-900">Payment & Coverage Information</h3>
        </button>
        <div data-section class="hidden border-t border-slate-200 px-6 py-4">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Specialization</p>
                    <p class="mt-1 text-base font-semibold text-slate-900">{{ $enrollment->specialization?->name ?: $na }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Plan Type</p>
                    <p class="mt-1 text-base font-semibold text-slate-900">{{ $planLabel }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Payment Mode</p>
                    <p class="mt-1 text-base font-semibold text-slate-900">{{ $enrollment->payment_mode ?: $na }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Coverage/Legal Service</p>
                    <p class="mt-1 text-base font-semibold text-slate-900">{{ $enrollment->coverage_id ?: $na }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Insurance Amount</p>
                    <p class="mt-1 text-base font-semibold text-slate-900">₹ {{ number_format((float) ($enrollment->service_amount ?? 0), 2) }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Medeforum Amount</p>
                    <p class="mt-1 text-base font-semibold text-slate-900">₹ {{ number_format((float) ($enrollment->payment_amount ?? 0), 2) }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Total Amount</p>
                    <p class="mt-1 text-lg font-bold text-emerald-600">₹ {{ number_format((float) ($enrollment->total_amount ?? 0), 2) }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Payment Method</p>
                    <p class="mt-1 text-base font-semibold text-slate-900">{{ $paymentMethodLabel }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Cheque No</p>
                    <p class="mt-1 font-mono text-slate-900">{{ $enrollment->payment_cheque ?: $na }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Bank Name</p>
                    <p class="mt-1 text-slate-900">{{ $enrollment->payment_bank_name ?: $na }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Branch Name</p>
                    <p class="mt-1 text-slate-900">{{ $enrollment->payment_branch_name ?: $na }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">UPI Transaction ID</p>
                    <p class="mt-1 font-mono text-slate-900">{{ $enrollment->payment_upi_transaction_id ?: $na }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Cash Date</p>
                    <p class="mt-1 text-slate-900">{{ optional($enrollment->payment_cash_date)->format('d-m-Y') ?: $na }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">Send Bond to Email</p>
                    <p class="mt-1 text-slate-900">{{ $enrollment->bond_to_mail ? '✓ Yes' : '✗ No' }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Approval Actions (Admin / Super Admin Only) -->
@if($isPrivilegedAdmin)
    <div class="my-8 rounded-2xl border-l-4 border-l-amber-500 bg-amber-50 p-6">
        <h3 class="mb-4 flex items-center gap-2 text-xl font-bold text-amber-900">
            <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
            Approval Decision Required
        </h3>

        <div class="grid gap-4 md:grid-cols-2">
            <button onclick="document.getElementById('approveModal').showModal()" class="rounded-lg bg-emerald-600 px-6 py-3 font-semibold text-white shadow transition hover:bg-emerald-700">
                ✓ Approve Enrollment
            </button>
            <button onclick="document.getElementById('rejectModal').showModal()" class="rounded-lg bg-rose-600 px-6 py-3 font-semibold text-white shadow transition hover:bg-rose-700">
                ✗ Reject Enrollment
            </button>
        </div>
    </div>

    <!-- Approve Modal -->
    <dialog id="approveModal" class="rounded-xl border border-slate-200 shadow-2xl backdrop:bg-black/50">
        <div class="w-full max-w-md p-6">
            <h2 class="mb-4 text-2xl font-bold text-slate-900">Approve Enrollment</h2>
            <form action="{{ route('admin.enrollment.approve', $enrollment->id) }}" method="post" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-900">Approval Remarks (Optional)</label>
                    <textarea name="approval_remarks" class="w-full rounded-lg border border-slate-300 px-4 py-2 focus:border-emerald-500 focus:outline-none" rows="4" placeholder="Add approval notes..."></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="document.getElementById('approveModal').close()" class="flex-1 rounded-lg border border-slate-300 px-4 py-2 font-semibold hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="flex-1 rounded-lg bg-emerald-600 px-4 py-2 font-semibold text-white hover:bg-emerald-700">Confirm</button>
                </div>
            </form>
        </div>
    </dialog>

    <!-- Reject Modal -->
    <dialog id="rejectModal" class="rounded-xl border border-slate-200 shadow-2xl backdrop:bg-black/50">
        <div class="w-full max-w-md p-6">
            <h2 class="mb-4 text-2xl font-bold text-slate-900">Reject Enrollment</h2>
            <form action="{{ route('admin.enrollment.reject', $enrollment->id) }}" method="post" class="space-y-4">
                @csrf
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-900">Rejection Reason</label>
                    <textarea name="rejection_reason" class="w-full rounded-lg border border-slate-300 px-4 py-2 focus:border-rose-500 focus:outline-none" rows="3" placeholder="Explain rejection..." required></textarea>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-900">Internal Remarks (Optional)</label>
                    <textarea name="approval_remarks" class="w-full rounded-lg border border-slate-300 px-4 py-2 focus:border-rose-500 focus:outline-none" rows="3" placeholder="Internal notes..."></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="document.getElementById('rejectModal').close()" class="flex-1 rounded-lg border border-slate-300 px-4 py-2 font-semibold hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="flex-1 rounded-lg bg-rose-600 px-4 py-2 font-semibold text-white hover:bg-rose-700">Confirm</button>
                </div>
            </form>
        </div>
    </dialog>
@endif

<!-- Continue to Step 2 (Approved) -->
@if($canProceedToStep2)
    <div class="rounded-lg border-l-4 border-l-emerald-500 bg-emerald-50 p-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="mb-2 text-xl font-bold text-emerald-900">Ready to Proceed</h3>
                <p class="text-sm text-emerald-800">This enrollment is approved. Continue to the next step.</p>
            </div>
            <a href="{{ route('admin.enrollment.step2', $enrollment) }}" class="rounded-lg bg-emerald-600 px-8 py-3 font-bold text-white shadow hover:bg-emerald-700">Proceed to Step 2</a>
        </div>
    </div>
@endif
@endsection

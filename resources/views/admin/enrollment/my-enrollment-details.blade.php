@extends('admin.layouts.app')

@section('title', 'My Enrollment Details')
@section('page-title', 'My Enrollment Details')

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
@endphp

<div class="mb-6 flex items-center gap-3">
    <a href="{{ route('admin.my-enrollments.index') }}" class="btn btn-ghost">← Back to My Enrollments</a>
</div>

<div class="mb-6 rounded-3xl border border-slate-200 bg-gradient-to-br from-slate-50 to-slate-100 p-6 shadow-sm">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">{{ $enrollment->doctor_name ?? $na }}</h1>
            <p class="mt-2 text-sm text-slate-600">Customer ID: <strong>{{ $enrollment->customer_id_no }}</strong></p>
            <p class="text-sm text-slate-600">Submitted on <strong>{{ optional($enrollment->created_at)->format('d M Y \a\t h:i A') ?? $na }}</strong></p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            @if($status === 'pending')
                <span class="inline-flex items-center gap-2 rounded-full bg-amber-100 px-4 py-2 text-sm font-semibold text-amber-700">Pending</span>
            @elseif($status === 'approved')
                <span class="inline-flex items-center gap-2 rounded-full bg-emerald-100 px-4 py-2 text-sm font-semibold text-emerald-700">Approved</span>
            @elseif($status === 'rejected')
                <span class="inline-flex items-center gap-2 rounded-full bg-rose-100 px-4 py-2 text-sm font-semibold text-rose-700">Rejected</span>
            @endif
        </div>
    </div>

    @if($status === 'pending')
        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <strong>Waiting for Approval:</strong> This enrollment is pending review. Step 2 is locked until approval.
        </div>
    @elseif($status === 'approved')
        <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            <strong>Admin has approved your enrollment.</strong> You can proceed to the next step.
        </div>
    @elseif($status === 'rejected')
        <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <strong>Rejected:</strong> This enrollment was rejected and remains locked.
        </div>
    @endif
</div>

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

<div class="space-y-4">
    <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <div class="border-b border-slate-200 px-6 py-4">
            <h3 class="text-lg font-semibold text-slate-900">Workflow & Creator Information</h3>
        </div>
        <div class="px-6 py-4">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                <div><p class="text-xs font-semibold uppercase text-slate-500">Created By</p><p class="mt-1 text-base font-semibold text-slate-900">{{ $creator?->name ?? $na }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Creator Role</p><p class="mt-1 text-base font-semibold text-slate-900">{{ ucfirst($enrollment->created_by_role ?: ($creator?->role ?? $na)) }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Creator Email</p><p class="mt-1 text-sm text-slate-700">{{ $creator?->email ?? $na }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Creator Mobile</p><p class="mt-1 text-sm text-slate-700">{{ $creator?->phone ?? $na }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Created At</p><p class="mt-1 text-sm text-slate-700">{{ optional($enrollment->created_at)->format('d M Y h:i A') ?? $na }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Created From</p><p class="mt-1 text-sm text-slate-700">{{ $latestActivity?->ip_address ?? $na }}</p></div>
                @if($enrollment->approved_by)
                    <div><p class="text-xs font-semibold uppercase text-slate-500">Approved By</p><p class="mt-1 text-base font-semibold text-emerald-600">{{ $approver?->name ?? $na }}</p></div>
                    <div><p class="text-xs font-semibold uppercase text-slate-500">Approved At</p><p class="mt-1 text-sm text-slate-700">{{ optional($enrollment->approved_at)->format('d M Y h:i A') ?? $na }}</p></div>
                @endif
                @if($enrollment->approval_remarks)
                    <div class="md:col-span-2 lg:col-span-3"><p class="text-xs font-semibold uppercase text-slate-500">Approval Remarks</p><p class="mt-1 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-900">{{ $enrollment->approval_remarks }}</p></div>
                @endif
                @if($enrollment->rejection_reason)
                    <div class="md:col-span-2 lg:col-span-3"><p class="text-xs font-semibold uppercase text-slate-500">Rejection Reason</p><p class="mt-1 rounded-lg bg-rose-50 p-3 text-sm text-rose-900">{{ $enrollment->rejection_reason }}</p></div>
                @endif
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <div class="border-b border-slate-200 px-6 py-4"><h3 class="text-lg font-semibold text-slate-900">Official Details</h3></div>
        <div class="px-6 py-4">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div><p class="text-xs font-semibold uppercase text-slate-500">Customer ID No</p><p class="mt-1 text-base font-mono font-semibold text-slate-900">{{ $enrollment->customer_id_no ?: $na }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Money Receipt No</p><p class="mt-1 text-base font-mono font-semibold text-slate-900">{{ $enrollment->money_rc_no ?: $na }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Broker / Agent Name</p><p class="mt-1 text-base font-semibold text-slate-900">{{ $enrollment->agent_name ?: $na }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Agent Phone No</p><p class="mt-1 text-base font-semibold text-slate-900">{{ $enrollment->agent_phone_no ?: $na }}</p></div>
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <div class="border-b border-slate-200 px-6 py-4"><h3 class="text-lg font-semibold text-slate-900">Uploaded Files & Documents</h3></div>
        <div class="px-6 py-4">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                    <p class="mb-3 font-semibold text-blue-900">Policy Receipts</p>
                    @if($enrollment->policyReceipts->isNotEmpty())
                        <ul class="space-y-2">
                            @foreach($enrollment->policyReceipts as $policyReceipt)
                                <li class="rounded border border-slate-200 bg-white p-3 text-sm">
                                    <div class="font-semibold text-slate-900">{{ $policyReceipt->policy_no ?: $na }}</div>
                                    <div class="mt-1 text-xs text-slate-600">{{ optional($policyReceipt->receive_date)->format('d M Y') ?: $na }}</div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm text-blue-700">No receipts uploaded.</p>
                    @endif
                </div>
                <div class="rounded-lg border border-purple-200 bg-purple-50 p-4">
                    <p class="mb-3 font-semibold text-purple-900">Doctor Documents</p>
                    @if($enrollment->doctorDocuments->isNotEmpty())
                        <ul class="space-y-2">
                            @foreach($enrollment->doctorDocuments as $document)
                                <li class="rounded border border-slate-200 bg-white p-3 text-sm">
                                    <div class="font-semibold text-slate-900">{{ $document->document_title ?: $na }}</div>
                                    <div class="mt-1 text-xs text-slate-600">{{ $document->document_type ?: $na }}</div>
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

    <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <div class="border-b border-slate-200 px-6 py-4"><h3 class="text-lg font-semibold text-slate-900">Proposer's Personal Details</h3></div>
        <div class="px-6 py-4">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                <div><p class="text-xs font-semibold uppercase text-slate-500">Full Name</p><p class="mt-1 text-base font-semibold text-slate-900">{{ $enrollment->doctor_name ?: $na }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Date of Birth</p><p class="mt-1 text-base font-semibold text-slate-900">{{ optional($enrollment->dob)->format('d/m/Y') ?: $na }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Email</p><p class="mt-1 text-sm text-slate-700">{{ $enrollment->doctor_email ?: $na }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Mobile 1</p><p class="mt-1 text-base font-semibold text-slate-900">{{ $enrollment->mobile1 ?: $na }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Mobile 2</p><p class="mt-1 text-base font-semibold text-slate-900">{{ $enrollment->mobile2 ?: $na }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Qualification</p><p class="mt-1 text-base font-semibold text-slate-900">{{ $enrollment->qualification ?: $na }}</p></div>
                @if($qualificationYears !== '')
                    <div><p class="text-xs font-semibold uppercase text-slate-500">Qualification Years</p><p class="mt-1 text-base font-semibold text-slate-900">{{ $qualificationYears }}</p></div>
                @endif
                <div><p class="text-xs font-semibold uppercase text-slate-500">Medical Reg No</p><p class="mt-1 text-base font-semibold text-slate-900">{{ $enrollment->medical_registration_no ?: $na }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Year of Registration</p><p class="mt-1 text-base font-semibold text-slate-900">{{ $enrollment->year_of_reg ?: $na }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Aadhaar Card</p><p class="mt-1 font-mono text-slate-900">{{ $enrollment->aadhar_card_no ?: $na }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">PAN Card</p><p class="mt-1 font-mono text-slate-900">{{ $enrollment->pan_card_no ?: $na }}</p></div>
                <div class="md:col-span-2 lg:col-span-3"><p class="text-xs font-semibold uppercase text-slate-500">Address</p><p class="mt-1 text-slate-900">{{ $enrollment->doctor_address ?: $na }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Country</p><p class="mt-1 text-slate-900">{{ $enrollment->country_name ?: $na }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">State</p><p class="mt-1 text-slate-900">{{ $enrollment->state_name ?: $na }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">City</p><p class="mt-1 text-slate-900">{{ $enrollment->city_name ?: $na }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Postcode</p><p class="mt-1 text-slate-900">{{ $enrollment->postcode ?: $na }}</p></div>
                <div class="md:col-span-2 lg:col-span-3"><p class="text-xs font-semibold uppercase text-slate-500">Clinic Address</p><p class="mt-1 text-slate-900">{{ $enrollment->clinic_address ?: $na }}</p></div>
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <div class="border-b border-slate-200 px-6 py-4"><h3 class="text-lg font-semibold text-slate-900">Payment & Coverage Information</h3></div>
        <div class="px-6 py-4">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                <div><p class="text-xs font-semibold uppercase text-slate-500">Specialization</p><p class="mt-1 text-base font-semibold text-slate-900">{{ $enrollment->specialization?->name ?: $na }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Plan Type</p><p class="mt-1 text-base font-semibold text-slate-900">{{ $planLabel }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Payment Mode</p><p class="mt-1 text-base font-semibold text-slate-900">{{ $enrollment->payment_mode ?: $na }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Coverage/Legal Service</p><p class="mt-1 text-base font-semibold text-slate-900">{{ $enrollment->coverage_id ?: $na }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Insurance Amount</p><p class="mt-1 text-base font-semibold text-slate-900">₹ {{ number_format((float) ($enrollment->service_amount ?? 0), 2) }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Medeforum Amount</p><p class="mt-1 text-base font-semibold text-slate-900">₹ {{ number_format((float) ($enrollment->payment_amount ?? 0), 2) }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Total Amount</p><p class="mt-1 text-lg font-bold text-emerald-600">₹ {{ number_format((float) ($enrollment->total_amount ?? 0), 2) }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Payment Method</p><p class="mt-1 text-base font-semibold text-slate-900">{{ $paymentMethodLabel }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Cheque No</p><p class="mt-1 font-mono text-slate-900">{{ $enrollment->payment_cheque ?: $na }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Bank Name</p><p class="mt-1 text-slate-900">{{ $enrollment->payment_bank_name ?: $na }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Branch Name</p><p class="mt-1 text-slate-900">{{ $enrollment->payment_branch_name ?: $na }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">UPI Transaction ID</p><p class="mt-1 font-mono text-slate-900">{{ $enrollment->payment_upi_transaction_id ?: $na }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Cash Date</p><p class="mt-1 text-slate-900">{{ optional($enrollment->payment_cash_date)->format('d-m-Y') ?: $na }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Send Bond to Email</p><p class="mt-1 text-slate-900">{{ $enrollment->bond_to_mail ? '✓ Yes' : '✗ No' }}</p></div>
            </div>
        </div>
    </div>
</div>

@if($status === 'approved')
    <div class="mt-8 rounded-lg border-l-4 border-l-emerald-500 bg-emerald-50 p-6">
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

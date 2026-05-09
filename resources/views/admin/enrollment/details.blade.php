@extends('admin.layouts.app')

@section('title', 'Enrollment Details')
@section('page-title', 'Enrollment Details')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.enrollment.pending') }}" class="btn">Back to Pending</a>
</div>

@php
    $na = 'N/A';
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
@endphp

<div class="section-card space-y-8">
    <div>
        <h3 class="mb-2 text-xl font-semibold text-slate-900">{{ $enrollment->doctor_name ?? $na }}</h3>
        <p class="text-sm text-slate-500">Submitted on {{ optional($enrollment->created_at)->format('d M Y h:i A') ?? $na }} | Current status: {{ ucfirst((string) ($enrollment->status ?? 'pending')) }}</p>
    </div>

    <section>
        <h4 class="mb-3 text-lg font-semibold text-slate-900">For Official Use</h4>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <p><strong>Customer ID No:</strong> {{ $enrollment->customer_id_no ?: $na }}</p>
            <p><strong>Money Receipt No:</strong> {{ $enrollment->money_rc_no ?: $na }}</p>
            <p><strong>Broker / Agent Name:</strong> {{ $enrollment->agent_name ?: $na }}</p>
            <p><strong>Agent Phone No:</strong> {{ $enrollment->agent_phone_no ?: $na }}</p>
        </div>
    </section>

    <section>
        <h4 class="mb-3 text-lg font-semibold text-slate-900">Proposer's Details</h4>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <p><strong>Name of the Proposer:</strong> {{ $enrollment->doctor_name ?: $na }}</p>
            <p><strong>Address:</strong> {{ $enrollment->doctor_address ?: $na }}</p>
            <p><strong>Country:</strong> {{ $enrollment->country_name ?: $na }}</p>
            <p><strong>State:</strong> {{ $enrollment->state_name ?: $na }}</p>
            <p><strong>City:</strong> {{ $enrollment->city_name ?: $na }}</p>
            <p><strong>Postcode:</strong> {{ $enrollment->postcode ?: $na }}</p>
            <p><strong>Mobile 1:</strong> {{ $enrollment->mobile1 ?: $na }}</p>
            <p><strong>Mobile 2 / Phone:</strong> {{ $enrollment->mobile2 ?: $na }}</p>
            <p><strong>Email:</strong> {{ $enrollment->doctor_email ?: $na }}</p>
            <p><strong>Date of Birth:</strong> {{ optional($enrollment->dob)->format('d/m/Y') ?: $na }}</p>
            <p><strong>Qualification:</strong> {{ $enrollment->qualification ?: $na }}</p>
            <p><strong>Qualification Year(s):</strong> {{ $qualificationYears !== '' ? $qualificationYears : $na }}</p>
            <p><strong>Medical Registration No:</strong> {{ $enrollment->medical_registration_no ?: $na }}</p>
            <p><strong>Year of Registration:</strong> {{ $enrollment->year_of_reg ?: $na }}</p>
            <p class="md:col-span-2"><strong>Clinic Address:</strong> {{ $enrollment->clinic_address ?: $na }}</p>
            <p><strong>Aadhaar Card No:</strong> {{ $enrollment->aadhar_card_no ?: $na }}</p>
            <p><strong>PAN Card No:</strong> {{ $enrollment->pan_card_no ?: $na }}</p>
        </div>
    </section>

    <section>
        <h4 class="mb-3 text-lg font-semibold text-slate-900">Payment Details</h4>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <p><strong>Specialization:</strong> {{ $enrollment->specialization?->name ?: $na }}</p>
            <p><strong>Payment Mode:</strong> {{ $enrollment->payment_mode ?: $na }}</p>
            <p><strong>Plan:</strong> {{ $planLabel }}</p>
            <p><strong>Coverage / Legal Service:</strong> {{ $enrollment->coverage_id ?: $na }}</p>
            <p><strong>Insurance Amount:</strong> {{ $enrollment->service_amount !== null ? $enrollment->service_amount : $na }}</p>
            <p><strong>Medeforum Amount:</strong> {{ $enrollment->payment_amount !== null ? $enrollment->payment_amount : $na }}</p>
            <p><strong>Total Amount:</strong> {{ $enrollment->total_amount !== null ? $enrollment->total_amount : $na }}</p>
            <p><strong>Payment Method:</strong> {{ $paymentMethodLabel }}</p>
            <p><strong>Cheque No:</strong> {{ $enrollment->payment_cheque ?: $na }}</p>
            <p><strong>Bank Name:</strong> {{ $enrollment->payment_bank_name ?: $na }}</p>
            <p><strong>Branch Name:</strong> {{ $enrollment->payment_branch_name ?: $na }}</p>
            <p><strong>UPI Transaction ID:</strong> {{ $enrollment->payment_upi_transaction_id ?: $na }}</p>
            <p><strong>Cash Date:</strong> {{ optional($enrollment->payment_cash_date)->format('d-m-Y') ?: $na }}</p>
            <p><strong>Send bond to email:</strong> {{ $enrollment->bond_to_mail ? 'Yes' : 'No' }}</p>
        </div>
    </section>

    <section class="border-t border-slate-200 pt-6">
        <h4 class="mb-3 text-lg font-semibold text-slate-900">Approval Actions</h4>
        <div class="flex flex-wrap items-start gap-3">
            <form action="{{ route('admin.enrollment.approve', $enrollment->id) }}" method="post">
                @csrf
                <button class="btn btn-success" type="submit">Approve Enrollment</button>
            </form>

            <form action="{{ route('admin.enrollment.reject', $enrollment->id) }}" method="post" class="w-full max-w-xl">
                @csrf
                <div class="form-group">
                    <label class="mb-2 block font-medium">Rejection reason (optional)</label>
                    <textarea name="rejection_reason" class="form-input" rows="3" placeholder="Enter reason if rejecting this enrollment"></textarea>
                </div>
                <button class="btn btn-danger" type="submit">Reject Enrollment</button>
            </form>
        </div>
    </section>
</div>
@endsection

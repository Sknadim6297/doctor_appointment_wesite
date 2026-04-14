@extends('admin.layouts.app')

@section('title', 'View Money Receipt')
@section('page-title', 'Account Management')

@section('content')
<style>
    .receipt-view-shell {
        background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
        border: 1px solid #dbe3ee;
        border-radius: 1rem;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        padding: 1.25rem;
    }
    .receipt-kv {
        display: grid;
        grid-template-columns: 180px 1fr;
        gap: 0.55rem 1rem;
    }
    .receipt-kv .k {
        font-size: 0.76rem;
        font-weight: 800;
        text-transform: uppercase;
        color: #475569;
    }
    .receipt-kv .v {
        font-size: 0.9rem;
        color: #0f172a;
        font-weight: 600;
    }
    .receipt-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        border-radius: 0.55rem;
        padding: 0.5rem 0.8rem;
        font-size: 0.78rem;
        font-weight: 700;
        line-height: 1;
        text-decoration: none;
        border: 0;
        cursor: pointer;
    }
    .receipt-btn-back { background: #475569; color: #fff; }
    .receipt-btn-print { background: #dc2626; color: #fff; }
    @media print {
        .no-print { display: none !important; }
        .receipt-view-shell { border: 0; box-shadow: none; padding: 0; }
    }
</style>

<div class="section-card space-y-5">
    <div class="receipt-view-shell">
        <div class="no-print mb-5 flex items-center justify-between">
            <div>
                <h3 class="section-title mb-1">Money Receipt</h3>
                <p class="text-sm text-slate-600">Receipt detail preview.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.receipts') }}" class="receipt-btn receipt-btn-back">
                    <i class="ri-arrow-left-line"></i>
                    Back
                </a>
                <button type="button" class="receipt-btn receipt-btn-print" onclick="window.print()">
                    <i class="ri-printer-line"></i>
                    Print
                </button>
            </div>
        </div>

        @php
            $planLabel = match((int) $receipt->plan) {
                1 => 'Normal',
                2 => 'High Risk',
                3 => 'Combo',
                default => $receipt->plan_name ?: 'N/A',
            };

            $paymentProcess = match ((int) $receipt->payment_method) {
                1 => 'Cheque',
                2 => 'Cash',
                3 => 'Online',
                default => (!empty($receipt->payment_upi_transaction_id) ? 'Online' : (!empty($receipt->payment_cheque) ? 'Cheque' : 'Cash')),
            };

            $transactionOrCheque = $receipt->payment_cheque ?: ($receipt->payment_upi_transaction_id ?: 'N/A');
            $bankDetails = $receipt->payment_bank_name ?: 'N/A';
            if (!empty($receipt->payment_branch_name)) {
                $bankDetails .= ' / ' . $receipt->payment_branch_name;
            }
        @endphp

        <div class="receipt-kv">
            <div class="k">Doctor</div><div class="v">{{ $receipt->doctor_name ?? 'N/A' }}</div>
            <div class="k">Membership No</div><div class="v">{{ $receipt->customer_id_no ?? 'N/A' }}</div>
            <div class="k">Money Receipt No</div><div class="v">{{ $receiptNoBase ?: 'N/A' }}</div>
            <div class="k">Money Receipt Year</div><div class="v">{{ $receiptNoYear ?: optional($receipt->created_at)->format('Y') ?: 'N/A' }}</div>
            <div class="k">Date</div><div class="v">{{ optional($receipt->payment_cash_date)->format('d/m/Y') ?: optional($receipt->created_at)->format('d/m/Y') ?: 'N/A' }}</div>
            <div class="k">Plan</div><div class="v">{{ $planLabel }}</div>
            <div class="k">Payment Process</div><div class="v">{{ $paymentProcess }}</div>
            <div class="k">Amount</div><div class="v">Rs. {{ number_format((float) ($receipt->payment_amount ?? 0), 0) }}/-</div>
            <div class="k">Insurance Amount</div><div class="v">Rs. {{ number_format((float) ($receipt->service_amount ?? 0), 0) }}/-</div>
            <div class="k">Cheque/Transaction ID</div><div class="v">{{ $transactionOrCheque }}</div>
            <div class="k">Bank Details</div><div class="v">{{ $bankDetails }}</div>
            <div class="k">Remarks</div><div class="v">None</div>
        </div>
    </div>
</div>
@endsection

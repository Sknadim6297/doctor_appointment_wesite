@extends('admin.layouts.app')

@section('title', 'Enrollment Cheque Deposit')
@section('page-title', 'Account Management')

@section('content')
<style>
    /* Reuse receipt styles for consistent admin look */
    .receipt-shell { background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%); border: 1px solid #dbe3ee; border-radius: 1rem; box-shadow: 0 1px 2px rgba(15,23,42,0.04); }
    .receipt-filter { background:#fff; border:1px solid #dbe3ee; border-radius:0.95rem; padding:1rem; }
    .receipt-btn { display:inline-flex; align-items:center; gap:0.35rem; border-radius:0.55rem; padding:0.5rem 0.8rem; font-size:0.78rem; font-weight:700; border:0; cursor:pointer; }
    .receipt-btn-view { background:#10b981; color:#fff; }
    .receipt-btn-doc { background:#2563eb; color:#fff; }
    .receipt-table { border-collapse: separate; border-spacing:0; border:1px solid #dbe3ee; border-radius:0.95rem; overflow:hidden; min-width:1000px; }
    .receipt-table thead th { background:#0f172a; color:#e2e8f0; font-size:0.72rem; font-weight:800; padding:0.8rem 0.7rem; }
    .receipt-table tbody td { font-size:0.85rem; padding:0.7rem; border-bottom:1px solid #e2e8f0; }
    @media print { .no-print { display:none !important; } }
</style>

<div class="section-card space-y-5">
    <div class="receipt-shell p-5 md:p-6">
        <div class="flex items-center justify-between no-print">
            <div>
                <h3 class="section-title mb-2">Enrollment cheque deposit ({{ $receipts->total() }})</h3>
                <p class="text-sm text-slate-600">Search month/year, print view and CSV export for enrollment cheque deposits.</p>
            </div>
            <div class="flex gap-2">
                <button type="button" class="receipt-btn receipt-btn-view" onclick="print_data()"><i class="ri-printer-line"></i> Print All</button>
                <a href="{{ route('admin.receipts.enrollment-cheque-deposit.csv', request()->query()) }}" class="receipt-btn receipt-btn-doc"><i class="ri-file-download-line"></i> Export CSV</a>
            </div>
        </div>

        <div class="receipt-filter mt-4 no-print">
            <form method="GET" action="{{ route('admin.receipts.enrollment-cheque-deposit') }}">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-12 md:items-end">
                    <div class="md:col-span-4 lg:col-span-3">
                        <label class="mb-1 block text-xs font-bold uppercase text-slate-500">Search Month</label>
                        <select name="search_month" class="w-full">
                            <option value="0">---Select Month---</option>
                            @foreach($months as $mNum => $mName)
                                <option value="{{ $mNum }}" {{ (int) ($searchMonth ?? 0) === (int) $mNum ? 'selected' : '' }}>{{ $mName }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-4 lg:col-span-3">
                        <label class="mb-1 block text-xs font-bold uppercase text-slate-500">Search Year</label>
                        <select name="search_year" class="w-full">
                            <option value="0">---Select Year---</option>
                            @foreach($years as $year)
                                <option value="{{ $year }}" {{ (int) ($searchYear ?? 0) === (int) $year ? 'selected' : '' }}>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-4 lg:col-span-3">
                        <label class="mb-1 block text-xs font-bold uppercase text-slate-500">&nbsp;</label>
                        <div class="flex gap-2">
                            <button type="submit" class="receipt-btn receipt-btn-view">Search</button>
                            <a href="{{ route('admin.receipts.enrollment-cheque-deposit') }}" class="receipt-btn receipt-btn-doc">Reset</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="mt-5 overflow-x-auto">
            <table class="receipt-table w-full">
                <thead>
                    <tr>
                        <th style="width:60px;">SL No</th>
                        <th>Doctor</th>
                        <th>Money reciept no.</th>
                        <th>Cheque no.</th>
                        <th>Deposit date</th>
                        <th>Amount</th>
                        <th>Payment for</th>
                        <th style="min-width:120px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($receipts as $receipt)
                        @php
                            $cheque = $receipt->payment_cheque ?: ($receipt->payment_upi_transaction_id ?: 'N.A');
                        @endphp
                        <tr>
                            <td><b>{{ $receipts->firstItem() + $loop->index }}</b></td>
                            <td><a target="_blank" href="{{ route('admin.doctors.show', $receipt->id) }}">{{ $receipt->doctor_name ?? 'N/A' }}</a></td>
                            <td>{{ $receipt->money_rc_no ?? 'N/A' }}</td>
                            <td>{{ $cheque }}</td>
                            <td>{{ optional($receipt->created_at)->format('d/m/Y') ?? 'N/A' }}</td>
                            <td><b>Rs. {{ number_format((float) ($receipt->payment_amount ?? 0), 0) }}/-</b></td>
                            <td>Enrollment</td>
                            <td>
                                <div class="flex gap-1">
                                    <button class="receipt-btn receipt-btn-doc" title="Delete record" onclick="delete_data({{ $receipt->id }})"><i class="ri-delete-bin-6-line"></i></button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-500">No enrollment cheque deposit records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($receipts->hasPages())
            <div class="mt-4">{{ $receipts->links() }}</div>
        @endif

        <div id="print_content" style="display:none;">
            <h3>Money reciept()</h3>
            <table class="table table-bordered" width="100%">
                <thead>
                    <tr>
                        <th>SL No</th>
                        <th>Doctor</th>
                        <th>Cheque no.</th>
                        <th>Deposit date</th>
                        <th>Amount</th>
                        <th>Payment for</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($receipts as $receipt)
                        @php $cheque = $receipt->payment_cheque ?: ($receipt->payment_upi_transaction_id ?: 'N.A'); @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $receipt->doctor_name ?? 'N/A' }}</td>
                            <td>{{ $cheque }}</td>
                            <td>{{ optional($receipt->created_at)->format('d/m/Y') ?? 'N/A' }}</td>
                            <td>Rs. {{ number_format((float) ($receipt->payment_amount ?? 0), 0) }}/-</td>
                            <td>Enrollment</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function print_data() {
        var divToPrint = document.getElementById('print_content');
        if (!divToPrint) { window.print(); return; }
        var newWin = window.open('','Print-Window');
        newWin.document.open();
        newWin.document.write('<html><body>' + divToPrint.outerHTML + '</body></html>');
        newWin.document.close();
        newWin.print();
        newWin.close();
    }

    function delete_data(id) {
        if (!confirm('Are you sure you want to delete record ' + id + '?')) return;
        alert('Delete is not implemented in this UI. Please use backend or contact admin.');
    }
</script>

@endsection

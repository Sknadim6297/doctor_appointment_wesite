@extends('admin.layouts.app')

@section('title', 'Premium Amount')
@section('page-title', 'Account Management')

@section('content')
<style>
    .premium-shell {
        background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
        border: 1px solid #dbe4f2;
        border-radius: 1rem;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }
    .premium-toolbar {
        background: #fff;
        border: 1px solid #dbe4f2;
        border-radius: 0.95rem;
        padding: 1rem;
    }
    .premium-filter label {
        display: block;
        margin-bottom: 0.35rem;
        font-size: 0.72rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        color: #475569;
    }
    .premium-filter select,
    .premium-filter input {
        width: 100%;
        height: 40px;
        border-radius: 0.6rem;
        border: 1px solid #cbd5e1;
        padding: 0.55rem 0.7rem;
        font-size: 0.85rem;
        color: #0f172a;
        background: #fff;
    }
    .premium-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        align-items: center;
        justify-content: flex-end;
    }
    .premium-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        border-radius: 0.55rem;
        padding: 0.52rem 0.8rem;
        font-size: 0.78rem;
        font-weight: 800;
        line-height: 1;
        text-decoration: none;
        transition: transform 0.15s ease, filter 0.15s ease;
        border: 0;
        cursor: pointer;
    }
    .premium-btn:hover { transform: translateY(-1px); filter: brightness(0.98); }
    .premium-btn-view { background: #2563eb; color: #fff; }
    .premium-btn-print { background: #0f766e; color: #fff; }
    .premium-btn-export { background: #16a34a; color: #fff; }
    .premium-action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        border-radius: 0.55rem;
        padding: 0.45rem 0.6rem;
        font-size: 0.75rem;
        font-weight: 800;
        line-height: 1;
        text-decoration: none;
        transition: transform 0.15s ease, filter 0.15s ease;
        border: 0;
        cursor: pointer;
        min-width: 34px;
        height: 34px;
    }
    .premium-action-btn:hover { transform: translateY(-1px); filter: brightness(0.98); }
    .premium-action-view { background: #10b981; color: #fff; }
    .premium-action-doc { background: #2563eb; color: #fff; }
    .premium-action-edit { background: #475569; color: #fff; }
    .premium-actions-cell {
        overflow: visible;
        white-space: nowrap;
    }
    .premium-table {
        border-collapse: separate;
        border-spacing: 0;
        border: 1px solid #dbe4f2;
        border-radius: 0.95rem;
        overflow: hidden;
        min-width: 1400px;
    }
    .premium-table thead th {
        background: #0f172a;
        color: #e2e8f0;
        font-size: 0.72rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        padding: 0.8rem 0.7rem;
        border-bottom: 1px solid #1e293b;
        white-space: nowrap;
    }
    .premium-table tbody td {
        font-size: 0.8rem;
        color: #0f172a;
        vertical-align: middle;
        padding: 0.8rem 0.7rem;
        border-bottom: 1px solid #e2e8f0;
    }
    .premium-table tbody tr:nth-child(even) { background: #f8fafc; }
    .premium-table tbody tr:hover { background: #eff6ff; }
    .premium-pill {
        display: inline-flex;
        align-items: center;
        border-radius: 9999px;
        padding: 0.25rem 0.6rem;
        font-size: 0.7rem;
        font-weight: 800;
        line-height: 1;
    }
    .premium-pill-coverage { background: #dbeafe; color: #1d4ed8; }
    .premium-pill-total { background: #dcfce7; color: #166534; }
    .premium-summary {
        display: grid;
        grid-template-columns: repeat(1, minmax(0, 1fr));
        gap: 0.75rem;
    }
    @media (min-width: 768px) {
        .premium-summary { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
    .premium-summary-card {
        border: 1px solid #dbe4f2;
        border-radius: 0.9rem;
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        padding: 0.9rem 1rem;
    }
    .premium-summary-card .label {
        font-size: 0.72rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        color: #64748b;
        margin-bottom: 0.25rem;
    }
    .premium-summary-card .value {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 1.25rem;
        font-weight: 700;
        color: #0f172a;
    }
    @media print {
        .no-print {
            display: none !important;
        }
    }
</style>

@php
    $resolvePremiumData = function ($doctor) use ($planCoverageMaps) {
        $planMap = $planCoverageMaps[(int) $doctor->plan] ?? collect();
        $planCoverage = null;

        if (filled($doctor->coverage_id)) {
            $planCoverage = $planMap->get((int) $doctor->coverage_id);
        }

        if (!$planCoverage) {
            $planCoverage = $planMap->first();
        }

        $premiumAmount = (float) ($doctor->payment_amount ?? 0);
        $gstAmount = (float) ($doctor->service_amount ?? 0);

        $commissionAmount = round($premiumAmount * 0.15, 2);
        $totalAmount = (float) ($doctor->total_amount ?? 0);
        if ($totalAmount <= 0) {
            $totalAmount = $premiumAmount + $gstAmount;
        }

        $coverageLabel = filled($doctor->coverage_id)
            ? ((string) $doctor->coverage_id . ' Lakh')
            : ($planCoverage?->coverage_lakh ? ((string) $planCoverage->coverage_lakh . ' Lakh') : 'N/A');

        return [
            'premium' => $premiumAmount,
            'gst' => $gstAmount,
            'commission' => $commissionAmount,
            'total' => $totalAmount,
            'coverage' => $coverageLabel,
            'renewal' => optional($doctor->created_at)->copy()?->addYear()?->format('d/m/Y') ?? 'N/A',
        ];
    };
@endphp

<section class="content">
    <div class="row">
        <div class="col-xs-12">
            <div class="section-card premium-shell space-y-5">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h3 class="section-title mb-2">Premium Amount ({{ $doctors->total() }})</h3>
                        <p class="text-sm text-slate-600">Premium, GST, commission, and renewal summary for enrolled doctors.</p>
                    </div>
                    <div class="premium-summary no-print w-full lg:max-w-xl">
                        <div class="premium-summary-card">
                            <div class="label">Records</div>
                            <div class="value">{{ $doctors->total() }}</div>
                        </div>
                        <div class="premium-summary-card">
                            <div class="label">Premium Total</div>
                            <div class="value">Rs. {{ number_format((float) ($totals->premium_total ?? 0), 0) }}/-</div>
                        </div>
                        <div class="premium-summary-card">
                            <div class="label">Grand Total</div>
                            <div class="value">Rs. {{ number_format((float) ($totals->total_total ?? 0), 0) }}/-</div>
                        </div>
                    </div>
                </div>

                <div class="premium-toolbar no-print">
                    <form method="GET" action="{{ route('admin.premium-amount.legacy-index') }}" class="space-y-4">
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-12 md:items-end premium-filter">
                            <div class="md:col-span-4 lg:col-span-3">
                                <label for="search_month">Search Month</label>
                                <select name="search_month" id="search_month">
                                    <option value="">---Select Month---</option>
                                    @foreach($months as $month)
                                        <option value="{{ $month }}" {{ $selectedMonth === $month ? 'selected' : '' }}>{{ $month }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="md:col-span-4 lg:col-span-3">
                                <label for="search_year">Search Year</label>
                                <select name="search_year" id="search_year">
                                    <option value="">---Select Year---</option>
                                    @foreach($years as $year)
                                        <option value="{{ $year }}" {{ (string) $selectedYear === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="md:col-span-4 lg:col-span-3">
                                <label>&nbsp;</label>
                                <div class="flex flex-wrap gap-2">
                                    <input type="submit" value="Search" onclick="return validate_add_doctor()" class="premium-btn premium-btn-view">
                                    <a href="{{ route('admin.premium-amount.legacy-index') }}" class="premium-btn premium-btn-view">Reset</a>
                                </div>
                            </div>

                            <div class="md:col-span-12 lg:col-span-3 premium-actions">
                                <a class="premium-btn premium-btn-print" href="javascript:void(0);" title="Print PDF" onclick="print_pdf();return false;">
                                    <i class="ri-printer-line" aria-hidden="true"></i>
                                    Print PDF
                                </a>
                                <a class="premium-btn premium-btn-export" href="{{ route('admin.premium-amount.legacy-csv', request()->query()) }}" title="Export CSV">
                                    Export CSV
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table id="example1" class="premium-table w-full">
                        <thead>
                            <tr>
                                <th style="width: 20px;"><input type="checkbox" name="all_chk" id="all_chk" onclick="check_all()"></th>
                                <th style="font-size: 0.85em;">SL No</th>
                                <th style="font-size: 0.85em;">DR. NAME</th>
                                <th style="font-size: 0.85em;">POLICY NO</th>
                                <th style="font-size: 0.85em;">INSURANCE COVERAGE</th>
                                <th style="font-size: 0.85em;">PREMIUM. AMT</th>
                                <th style="font-size: 0.85em;">GST</th>
                                <th style="font-size: 0.85em;">COMMISSION</th>
                                <th style="font-size: 0.85em;">TOTAL. AMT</th>
                                <th style="font-size: 0.85em;">RENEWAL<br>DATE</th>
                                <th style="font-size: 0.85em;">ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($doctors as $doctor)
                                @php($amount = $resolvePremiumData($doctor))
                                <tr>
                                    <td><input type="checkbox" name="record" value="{{ $doctor->id }}"></td>
                                    <td style="font-size: 0.85em"><b>{{ $doctors->firstItem() + $loop->index }}</b></td>
                                    <td style="font-size: 0.85em"><b><a target="_blank" href="{{ route('admin.doctors.show', $doctor->id) }}?tab=membership">{{ $doctor->doctor_name ?? 'N/A' }}</a></b></td>
                                    <td style="font-size: 0.85em"><b>{{ $doctor->money_rc_no ?? 'N/A' }}</b></td>
                                    <td style="font-size: 0.85em"><span class="premium-pill premium-pill-coverage">{{ $amount['coverage'] }}</span></td>
                                    <td style="font-size: 0.85em"><b>Rs.{{ number_format($amount['premium'], 0) }}/-</b></td>
                                    <td style="font-size: 0.85em"><b>Rs. {{ number_format($amount['gst'], 0) }}/-</b></td>
                                    <td style="font-size: 0.85em"><b>Rs. {{ number_format($amount['commission'], 0) }}/-</b></td>
                                    <td style="font-size: 0.85em"><span class="premium-pill premium-pill-total">Rs. {{ number_format($amount['total'], 0) }}/-</span></td>
                                    <td style="font-size: 0.85em"><b>{{ $amount['renewal'] }}</b></td>
                                    <td class="premium-actions-cell" style="font-size: 0.85em">
                                        <div class="flex flex-wrap gap-1">
                                            <a class="premium-action-btn premium-action-view" href="{{ route('admin.doctors.show', $doctor->id) }}?tab=premium_send" target="_blank" title="View premium send">
                                                <i class="ri-eye-line"></i>
                                            </a>
                                            <a class="premium-action-btn premium-action-doc" href="{{ route('admin.policy-receipt.legacy-create', $doctor->id) }}" target="_blank" title="Submit policy recieved">
                                                <i class="ri-add-circle-line" aria-hidden="true"></i>
                                            </a>
                                            <a class="premium-action-btn premium-action-edit" title="Edit" href="{{ route('admin.enrollment.legacy-edit', $doctor->id) }}">
                                                <i class="ri-pencil-line"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" style="text-align:center;">No premium records found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <thead>
                            <tr>
                                <th style="font-size: 0.85em;"></th>
                                <th style="font-size: 0.85em;"></th>
                                <th style="font-size: 0.85em;"></th>
                                <th style="font-size: 0.85em;"></th>
                                <th style="font-size: 0.95em; text-align: right;">Total</th>
                                <th style="font-size: 0.85em;">Rs. {{ number_format((float) ($totals->premium_total ?? 0), 0) }}/-</th>
                                <th style="font-size: 0.85em;"></th>
                                <th style="font-size: 0.85em;">Rs. {{ number_format(((float) ($totals->premium_total ?? 0) * 0.15), 0) }}/-</th>
                                <th style="font-size: 0.95em;">Rs. {{ number_format((float) ($totals->total_total ?? 0), 0) }}/-</th>
                                <th style="font-size: 0.85em;"></th>
                                <th style="font-size: 0.85em;"></th>
                            </tr>
                        </thead>
                    </table>
                </div>

                <div class="row no-print" style="margin-top: 10px;">
                    <div class="col-sm-5">
                        Showing {{ $doctors->firstItem() ?? 0 }} to {{ $doctors->lastItem() ?? 0 }} of {{ $doctors->total() }} entries
                    </div>
                    <div class="col-sm-7 text-right">
                        {{ $doctors->links() }}
                    </div>
                </div>

                <div id="print_content" style="display:none;">
                    <h3 class="box-title">Premium Amount({{ $doctors->total() }})</h3>
                    <style type="text/css">
                        #print_content td, #print_content th {
                            border: solid 1px #777;
                            margin: 2px;
                            padding: 2px;
                        }
                    </style>

                    <table class="table table-bordered table-striped" width="100%">
                        <thead>
                            <tr>
                                <th style="font-size: 0.85em;">SL No</th>
                                <th style="font-size: 0.85em;">DR. NAME</th>
                                <th style="font-size: 0.85em;">POLICY NO</th>
                                <th style="font-size: 0.85em;">PREMIUM. AMT</th>
                                <th style="font-size: 0.85em;">GST</th>
                                <th style="font-size: 0.85em;">TOTAL. AMT</th>
                                <th style="font-size: 0.85em;">RENEWAL<br>DATE</th>
                                <th style="font-size: 0.85em;">INSURANCE COVERAGE</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($printDoctors as $doctor)
                                @php($amount = $resolvePremiumData($doctor))
                                <tr>
                                    <td style="font-size: 0.85em"><b>{{ $loop->iteration }}</b></td>
                                    <td style="font-size: 0.85em"><b>{{ $doctor->doctor_name ?? 'N/A' }}</b></td>
                                    <td style="font-size: 0.85em"><b>{{ $doctor->money_rc_no ?? 'N/A' }}</b></td>
                                    <td style="font-size: 0.85em"><b>Rs.{{ number_format($amount['premium'], 0) }}/-</b></td>
                                    <td style="font-size: 0.85em"><b>Rs. {{ number_format($amount['gst'], 0) }}/-</b></td>
                                    <td style="font-size: 0.85em"><b>Rs. {{ number_format($amount['total'], 0) }}/-</b></td>
                                    <td style="font-size: 0.85em"><b>{{ $amount['renewal'] }}</b></td>
                                    <td style="font-size: 0.85em"><b>{{ $amount['coverage'] }}</b></td>
                                </tr>
                            @endforeach
                        </tbody>
                        <thead>
                            <tr>
                                <th style="font-size: 0.85em;"></th>
                                <th style="font-size: 0.85em;"></th>
                                <th style="font-size: 0.95em; text-align: right;">Total</th>
                                <th style="font-size: 0.85em;">Rs. {{ number_format((float) ($totals->premium_total ?? 0), 0) }}/-</th>
                                <th style="font-size: 0.85em;"></th>
                                <th style="font-size: 0.95em;">Rs. {{ number_format((float) ($totals->total_total ?? 0), 0) }}/-</th>
                                <th style="font-size: 0.85em;"></th>
                                <th style="font-size: 0.85em;"></th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    function validate_add_doctor() {
        return true;
    }

    function check_all() {
        const root = document.getElementById('example1');
        if (!root) {
            return;
        }
        const master = document.getElementById('all_chk');
        const records = root.querySelectorAll('input[name="record"]');
        records.forEach(function (checkbox) {
            checkbox.checked = !!master.checked;
        });
    }

    function print_pdf() {
        const divToPrint = document.getElementById('print_content');
        if (!divToPrint) {
            return;
        }

        const printWindow = window.open('', '_blank');
        if (!printWindow) {
            return;
        }

        printWindow.document.write('<html><head><title>Premium Amount</title></head><body>' + divToPrint.innerHTML + '</body></html>');
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
        printWindow.close();
    }
</script>
@endpush

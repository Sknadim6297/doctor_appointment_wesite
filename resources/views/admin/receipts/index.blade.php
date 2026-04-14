@extends('admin.layouts.app')

@section('title', 'Money Receipt')
@section('page-title', 'Account Management')

@section('content')
<style>
    .receipt-shell {
        background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
        border: 1px solid #dbe3ee;
        border-radius: 1rem;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }
    .receipt-banner {
        background: linear-gradient(135deg, #ecfeff 0%, #f0f9ff 100%);
        border: 1px solid #bae6fd;
        color: #0c4a6e;
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
        transition: transform 0.15s ease, filter 0.15s ease;
        border: 0;
        cursor: pointer;
    }
    .receipt-btn:hover { transform: translateY(-1px); filter: brightness(0.98); }
    .receipt-btn-view { background: #10b981; color: #fff; }
    .receipt-btn-edit { background: #475569; color: #fff; }
    .receipt-btn-doc { background: #2563eb; color: #fff; }
    .receipt-btn-renew { background: #dc2626; color: #fff; }
    .receipt-table {
        border-collapse: separate;
        border-spacing: 0;
        border: 1px solid #dbe3ee;
        border-radius: 0.95rem;
        overflow: hidden;
        min-width: 1500px;
    }
    .receipt-table thead th {
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
    .receipt-table tbody td {
        font-size: 0.8rem;
        color: #0f172a;
        vertical-align: middle;
        padding: 0.8rem 0.7rem;
        border-bottom: 1px solid #e2e8f0;
    }
    .receipt-table tbody tr:nth-child(even) { background: #f8fafc; }
    .receipt-table tbody tr:hover { background: #eff6ff; }
    .receipt-pill {
        display: inline-flex;
        align-items: center;
        border-radius: 9999px;
        padding: 0.25rem 0.6rem;
        font-size: 0.7rem;
        font-weight: 800;
        line-height: 1;
    }
    .receipt-pill-normal { background: #dbeafe; color: #1d4ed8; }
    .receipt-pill-high { background: #fef3c7; color: #92400e; }
    .receipt-pill-combo { background: #ede9fe; color: #6d28d9; }
    .receipt-pill-default { background: #e2e8f0; color: #334155; }
    .receipt-filter {
        background: #fff;
        border: 1px solid #dbe3ee;
        border-radius: 0.95rem;
        padding: 1rem;
    }
    .receipt-filter select,
    .receipt-filter input {
        height: 40px;
        border-radius: 0.6rem;
        border-color: #cbd5e1;
        font-size: 0.85rem;
    }
    .receipt-modal-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.65);
        z-index: 70;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }
    .receipt-modal-backdrop.show {
        display: flex;
    }
    .receipt-modal {
        width: min(100%, 760px);
        max-height: 90vh;
        overflow: auto;
        border-radius: 1rem;
        background: #fff;
        border: 1px solid #dbe3ee;
        box-shadow: 0 24px 64px rgba(2, 8, 23, 0.24);
    }
    .receipt-modal .modal-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid #e2e8f0;
    }
    .receipt-modal .modal-body {
        padding: 1rem 1.25rem;
    }
    .receipt-modal .modal-foot {
        display: flex;
        justify-content: flex-end;
        gap: 0.5rem;
        padding: 1rem 1.25rem;
        border-top: 1px solid #e2e8f0;
        background: #f8fafc;
    }
    .receipt-form-grid {
        display: grid;
        grid-template-columns: repeat(1, minmax(0, 1fr));
        gap: 0.85rem;
    }
    @media (min-width: 768px) {
        .receipt-form-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .receipt-form-grid .full {
            grid-column: span 2;
        }
    }
    .receipt-field label {
        display: block;
        margin-bottom: 0.3rem;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        color: #475569;
    }
    .receipt-field input,
    .receipt-field select,
    .receipt-field textarea {
        width: 100%;
        border: 1px solid #cbd5e1;
        border-radius: 0.6rem;
        font-size: 0.86rem;
        padding: 0.55rem 0.65rem;
        color: #0f172a;
        background: #fff;
    }
    .receipt-field textarea {
        min-height: 76px;
    }
    .receipt-required {
        color: #dc2626;
    }
    .receipt-text-muted {
        font-size: 0.74rem;
        color: #64748b;
    }
    @media print {
        .no-print {
            display: none !important;
        }
        body {
            background: #fff !important;
        }
        .receipt-shell {
            border: 0;
            box-shadow: none;
            padding: 0;
        }
        .receipt-table {
            min-width: 0;
        }
    }
</style>

<div class="section-card space-y-5">
    <div class="receipt-shell p-5 md:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between no-print">
            <div>
                <h3 class="section-title mb-2">Money Receipt List</h3>
                <p class="text-sm text-slate-600">
                    Account management receipts with month/year filters, print view, CSV export, and quick actions.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="button" class="receipt-btn receipt-btn-view" onclick="openAddReceiptModal()">
                    <i class="ri-user-add-line"></i>
                    Add money receipt
                </button>
                <a href="{{ route('admin.receipts.csv-report', request()->query()) }}" class="receipt-btn receipt-btn-doc">
                    <i class="ri-file-download-line"></i>
                    Export CSV
                </a>
                <button type="button" class="receipt-btn receipt-btn-renew" onclick="print_data()">
                    <i class="ri-printer-line"></i>
                    Print All
                </button>
            </div>
        </div>

        <div class="receipt-filter mt-5 no-print">
            <form method="GET" action="{{ route('admin.receipts') }}" class="space-y-4">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-12 md:items-end">
                    <div class="md:col-span-4 lg:col-span-3">
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500" for="search_month">Search Month</label>
                        <select name="search_month" id="search_month" class="w-full">
                            <option value="0">---Select Month---</option>
                            @foreach($months as $monthNumber => $monthName)
                                <option value="{{ $monthNumber }}" {{ (int) $searchMonth === (int) $monthNumber ? 'selected' : '' }}>{{ $monthName }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-4 lg:col-span-3">
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500" for="search_year">Search Year</label>
                        <select name="search_year" id="search_year" class="w-full">
                            <option value="0">---Select Year---</option>
                            @foreach($years as $year)
                                <option value="{{ $year }}" {{ (int) $searchYear === (int) $year ? 'selected' : '' }}>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-4 lg:col-span-3">
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500" for="search">Search Doctor / Receipt</label>
                        <input type="text" name="search" id="search" value="{{ $searchText }}" placeholder="Doctor, receipt no, membership, phone" class="w-full">
                    </div>
                    <div class="md:col-span-12 lg:col-span-3 flex flex-wrap gap-2">
                        <button type="submit" class="receipt-btn receipt-btn-view px-4">
                            <i class="ri-search-line"></i>
                            Search
                        </button>
                        <a href="{{ route('admin.receipts') }}" class="receipt-btn receipt-btn-doc px-4">
                            <i class="ri-refresh-line"></i>
                            Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        @if(session('success'))
            <div class="receipt-banner mt-4 rounded-xl px-4 py-3 text-sm font-semibold">
                {{ session('success') }}
            </div>
        @endif

        <div class="mt-5 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900">
            Showing {{ $receipts->total() }} receipt records. Total amount: <b>Rs. {{ number_format((float) ($summary->total_payment_amount ?? 0), 0) }}/-</b> | Insurance amount: <b>Rs. {{ number_format((float) ($summary->total_service_amount ?? 0), 0) }}/-</b>.
        </div>

        <div class="mt-5 overflow-x-auto">
            <table class="receipt-table w-full">
                <thead>
                    <tr>
                        <th style="width: 60px;">SL No</th>
                        <th>MONEY RECEIPT NO.</th>
                        <th>DR. NAME</th>
                        <th>MEM. NO</th>
                        <th>YEAR</th>
                        <th>DATE</th>
                        <th>AMOUNT</th>
                        <th>PLAN</th>
                        <th>CHEQUE NO./TRANSACTION ID</th>
                        <th>BANK DETAILS</th>
                        <th>REMARKS</th>
                        <th style="min-width: 235px;">ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($receipts as $receipt)
                        @php
                            $planLabel = match((int) $receipt->plan) {
                                1 => 'Normal',
                                2 => 'High Risk',
                                3 => 'Combo',
                                default => $receipt->plan_name ?: 'N/A',
                            };
                            $planClass = match((int) $receipt->plan) {
                                1 => 'receipt-pill-normal',
                                2 => 'receipt-pill-high',
                                3 => 'receipt-pill-combo',
                                default => 'receipt-pill-default',
                            };
                            $transactionId = $receipt->payment_cheque ?: ($receipt->payment_upi_transaction_id ?: '—');
                            $bankDetails = $receipt->payment_bank_name ?: '—';
                            if (!empty($receipt->payment_branch_name)) {
                                $bankDetails .= ' / ' . $receipt->payment_branch_name;
                            }
                        @endphp
                        <tr>
                            <td><b>{{ $receipts->firstItem() + $loop->index }}</b></td>
                            <td><b>{{ $receipt->money_rc_no ?? '—' }}</b></td>
                            <td>
                                <b>
                                    <a href="{{ route('admin.doctors.show', $receipt->id) }}" target="_blank">{{ $receipt->doctor_name ?? '—' }}</a>
                                </b>
                            </td>
                            <td><b>{{ $receipt->customer_id_no ?? '—' }}</b></td>
                            <td><b>{{ optional($receipt->created_at)->format('Y') ?? '—' }}</b></td>
                            <td><b>{{ optional($receipt->created_at)->format('d/m/Y') ?? '—' }}</b></td>
                            <td><b>{{ filled($receipt->payment_amount) ? 'Rs. ' . number_format((float) $receipt->payment_amount, 0) . '/-' : '—' }}</b></td>
                            <td><span class="receipt-pill {{ $planClass }}">{{ $planLabel }}</span></td>
                            <td>{{ $transactionId }}</td>
                            <td>{{ $bankDetails }}</td>
                            <td><b>None</b></td>
                            <td>
                                <div class="flex flex-wrap gap-1">
                                    <button type="button" id="resend_money_rcpt_btn-{{ $receipt->id }}" class="receipt-btn receipt-btn-doc" title="Resend money receipt" onclick="resend_money_receipt({{ $receipt->id }}, @js($receipt->doctor_email))">
                                        <i class="ri-reply-line"></i>
                                    </button>
                                    <a href="{{ route('admin.receipts.view', $receipt->id) }}" target="_blank" class="receipt-btn receipt-btn-view" title="View money receipt">
                                        <i class="ri-eye-line"></i>
                                    </a>
                                    <button type="button" class="receipt-btn receipt-btn-edit" title="Edit money receipt" onclick="openEditReceiptModal({{ $receipt->id }})">
                                        <i class="ri-pencil-line"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="py-8 text-center text-slate-500">
                                No money receipt records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($receipts->hasPages())
            <div class="mt-6">
                {{ $receipts->links() }}
            </div>
        @endif
    </div>
</div>

<div id="edit_money_rcpt_modal" class="receipt-modal-backdrop no-print" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="receipt-modal">
        <div class="modal-head">
            <h4 class="text-base font-extrabold text-slate-800">Edit money receipt</h4>
            <button type="button" class="text-slate-400 hover:text-slate-700" onclick="closeEditReceiptModal()">
                <i class="ri-close-line text-xl"></i>
            </button>
        </div>

        <form action="{{ route('admin.receipts.legacy-update') }}" method="POST" id="edit_renew_form_validation" onsubmit="return renew_edit_doctor_validation();">
            @csrf
            <input type="hidden" name="money_rc_id" id="edit_money_rc_id" value="">
            <input type="hidden" name="payment_data_id" id="edit_payment_data_id" value="">
            <div class="modal-body space-y-4">
                <div class="receipt-form-grid">
                    <div class="receipt-field full">
                        <label for="edit_doctor">Select Doctor <span class="receipt-required">*</span></label>
                        <select name="doctor" id="edit_doctor" onchange="edit_doctor_select(this.value);" required>
                            <option value="">--Select doctor--</option>
                            @foreach($doctors as $doctor)
                                <option value="{{ $doctor->id }}">{{ $doctor->doctor_name }}{{ $doctor->money_rc_no ? ' (' . $doctor->money_rc_no . ')' : '' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="receipt-field">
                        <label for="edit_money_reciept_membership_no">Membership no.</label>
                        <input type="text" id="edit_money_reciept_membership_no" readonly>
                    </div>

                    <div class="receipt-field">
                        <label for="edit_speciliazition">Specialization</label>
                        <select name="speciliazition" id="edit_speciliazition" onchange="edit_onchange_spec();">
                            <option value="0">---Select specialization---</option>
                            @foreach($specializations as $specialization)
                                <option value="{{ $specialization->id }}">{{ $specialization->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="receipt-field">
                        <label for="edit_total_amount" class="control-label">Amount</label>
                        <input type="number" min="0" step="0.01" name="payment_amount" id="edit_total_amount" class="form-control" value="">
                    </div>

                    <div class="receipt-field">
                        <label>Money receipt no.</label>
                        <input type="text" class="form-control" name="money_reciept_no" id="edit_money_reciept_no" value="">
                    </div>

                    <div class="receipt-field">
                        <label>Money receipt year</label>
                        <select class="form-control" name="money_reciept_year" id="edit_money_reciept_year">
                            <option value="0">---Select Year---</option>
                            @for($year = 2016; $year <= (int) date('Y'); $year++)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endfor
                        </select>
                    </div>

                    <div class="receipt-field">
                        <label>Payment process</label>
                        <select class="form-control" name="payment_process" id="edit_payment_process" onchange="edit_change_payment_process();">
                            <option value="cash">Cash</option>
                            <option value="cheque">Cheque</option>
                            <option value="Online">Online</option>
                        </select>
                    </div>

                    <div class="receipt-field">
                        <label>Payment mode</label>
                        <select class="form-control" name="payment_mode" id="edit_payment_mode" onchange="edit_onchange_payment_mode();">
                            <option value="Cash">Cash</option>
                            <option value="Cheque">Cheque</option>
                            <option value="Online">Online</option>
                        </select>
                    </div>

                    <div class="receipt-field">
                        <label>Payment date</label>
                        <input type="date" class="form-control" name="payment_date" id="edit_payment_date" value="">
                    </div>

                    <div id="edit_appear_check_payment" class="full receipt-form-grid" style="display: none; grid-template-columns: repeat(1, minmax(0, 1fr));">
                        <div class="receipt-field">
                            <label>Cheque no.</label>
                            <input type="text" class="form-control" name="cheque_no" id="edit_cheque_no" value="">
                        </div>
                        <div class="receipt-field">
                            <label>Bank</label>
                            <input type="text" class="form-control" name="payment_bank" id="edit_payment_bank" value="">
                        </div>
                        <div class="receipt-field">
                            <label>Branch</label>
                            <input type="text" class="form-control" name="payment_branch" id="edit_payment_branch" value="">
                        </div>
                    </div>

                    <div id="edit_appear_online_payment" class="full receipt-form-grid" style="display: none; grid-template-columns: repeat(1, minmax(0, 1fr));">
                        <div class="receipt-field">
                            <label>Transaction no.</label>
                            <input type="text" class="form-control" name="transaction_no" id="edit_transaction_no" value="">
                        </div>
                    </div>

                    <div class="receipt-field" style="display: none;">
                        <label>Money receipt for</label>
                        <select class="form-control" name="money_rc_for" id="edit_money_rc_for">
                            <option value="renewal">Renewal</option>
                            <option value="enrollment" selected>Enrollment</option>
                        </select>
                    </div>

                    <div class="receipt-field full">
                        <label>Remarks</label>
                        <input type="text" class="form-control" name="money_remarks" id="edit_money_remarks" placeholder="Give remarks" value="">
                    </div>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="receipt-btn receipt-btn-edit" onclick="closeEditReceiptModal()">Close</button>
                <button type="submit" id="edit_doctor_submit_btn" class="receipt-btn receipt-btn-view">Submit</button>
            </div>
        </form>
    </div>
</div>

<div id="add_money_rcpt_modal" class="receipt-modal-backdrop no-print" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="receipt-modal">
        <div class="modal-head">
            <h4 class="text-base font-extrabold text-slate-800">Add money receipt</h4>
            <button type="button" class="text-slate-400 hover:text-slate-700" onclick="closeAddReceiptModal()">
                <i class="ri-close-line text-xl"></i>
            </button>
        </div>

        <form action="{{ route('admin.receipts.store') }}" method="POST" id="renew_form_validation" onsubmit="return renew_doctor_validation();">
            @csrf
            <div class="modal-body space-y-4">
                <div class="receipt-form-grid">
                    <div class="receipt-field full">
                        <label for="doctor">Select Doctor <span class="receipt-required">*</span></label>
                        <select name="doctor" id="doctor" onchange="doctor_select(this.value);" required>
                            <option value="">--Select doctor--</option>
                            @foreach($doctors as $doctor)
                                <option value="{{ $doctor->id }}">{{ $doctor->doctor_name }}{{ $doctor->money_rc_no ? ' (' . $doctor->money_rc_no . ')' : '' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="receipt-field">
                        <label for="money_reciept_membership_no">Membership no.</label>
                        <input type="text" id="money_reciept_membership_no" name="money_reciept_membership_no" readonly>
                    </div>

                    <div class="receipt-field">
                        <label for="speciliazition">Specialization</label>
                        <select name="speciliazition" id="speciliazition" onchange="onchange_spec();">
                            <option value="0">---Select specialization---</option>
                            @foreach($specializations as $specialization)
                                <option value="{{ $specialization->id }}">{{ $specialization->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="full border-t border-slate-200 pt-3">
                        <h5 class="text-sm font-extrabold text-slate-700">Payment details</h5>
                    </div>

                    <div class="receipt-field">
                        <label for="payment_mode">Payment Mode <span class="receipt-required">*</span></label>
                        <select name="payment_mode" id="payment_mode" onchange="onchange_payment_mode();" required>
                            <option value="">---Select Payment Mode---</option>
                            <option value="Cash">Cash</option>
                            <option value="Cheque">Cheque</option>
                            <option value="Online">Online</option>
                        </select>
                    </div>

                    <div class="receipt-field">
                        <label for="plan">Plan <span class="receipt-required">*</span></label>
                        <input type="hidden" name="plan_name" id="plan_name" value="">
                        <select name="plan" id="plan" onchange="change_plan_id_to_name(); return chng_plan(this.value);" required>
                            <option value="0">---Select plan---</option>
                            <option value="1">Normal</option>
                            <option value="2">High Risk</option>
                            <option value="3">Combo</option>
                        </select>
                    </div>

                    <div class="receipt-field">
                        <label for="coverage" id="coverage_text">Legal Service</label>
                        <select name="coverage" id="coverage">
                            <option value="">--Select Coverage--</option>
                        </select>
                    </div>

                    <div class="receipt-field" id="pre_amount_label" style="display:none;">
                        <label for="pre_amount">Insurance Amount</label>
                        <input type="text" name="service_amount" id="pre_amount" readonly>
                    </div>

                    <div class="receipt-field">
                        <label for="payment_amount">Medeforum Amount <span class="receipt-required">*</span></label>
                        <input type="number" min="0" step="0.01" name="payment_amount" id="payment_amount" required>
                    </div>

                    <div class="receipt-field" id="total_amount_label" style="display:none;">
                        <label for="total_amount">Total Amount</label>
                        <input type="text" name="total_amount" id="total_amount" readonly>
                    </div>

                    <div class="receipt-field">
                        <label for="money_reciept_no">Money receipt no. <span class="receipt-required">*</span></label>
                        <input type="text" name="money_reciept_no" id="money_reciept_no" placeholder="E.g.: 0001" inputmode="numeric" required>
                    </div>

                    <div class="receipt-field">
                        <label for="money_reciept_year">Money receipt year</label>
                        <select name="money_reciept_year" id="money_reciept_year">
                            <option value="0">---Select Year---</option>
                            @for($year = 2016; $year <= date('Y'); $year++)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endfor
                        </select>
                    </div>

                    <div class="receipt-field">
                        <label for="payment_process">Payment process</label>
                        <select name="payment_process" id="payment_process" onchange="change_payment_process();">
                            <option value="cash">Cash</option>
                            <option value="cheque">Cheque</option>
                            <option value="Online">Online</option>
                        </select>
                    </div>

                    <div class="receipt-field">
                        <label for="payment_date">Payment date</label>
                        <input type="date" name="payment_date" id="payment_date">
                    </div>

                    <div id="appear_check_payment" class="full receipt-form-grid" style="display:none; grid-template-columns: repeat(1, minmax(0, 1fr));">
                        <div class="receipt-field">
                            <label for="cheque_no">Cheque no.</label>
                            <input type="text" name="cheque_no" id="cheque_no">
                        </div>
                        <div class="receipt-field">
                            <label for="payment_bank">Bank</label>
                            <input type="text" name="payment_bank" id="payment_bank">
                        </div>
                        <div class="receipt-field">
                            <label for="payment_branch">Branch</label>
                            <input type="text" name="payment_branch" id="payment_branch">
                        </div>
                    </div>

                    <div id="appear_online_payment" class="full receipt-form-grid" style="display:none; grid-template-columns: repeat(1, minmax(0, 1fr));">
                        <div class="receipt-field">
                            <label for="transaction_no">Transaction no.</label>
                            <input type="text" name="transaction_no" id="transaction_no">
                        </div>
                    </div>

                    <div class="receipt-field">
                        <label for="money_rc_for">Money receipt for</label>
                        <select name="money_rc_for" id="money_rc_for">
                            <option value="renewal">Renewal</option>
                            <option value="enrollment">Enrollment</option>
                        </select>
                    </div>

                    <div class="receipt-field full">
                        <label for="money_remarks">Remarks</label>
                        <textarea name="money_remarks" id="money_remarks" placeholder="Give remarks"></textarea>
                        <div class="receipt-text-muted">Optional note for finance/account records.</div>
                    </div>
                </div>
            </div>

            <div class="modal-foot">
                <button type="button" class="receipt-btn receipt-btn-edit" onclick="closeAddReceiptModal()">Close</button>
                <button type="submit" id="doctor_submit_btn" class="receipt-btn receipt-btn-view">Submit</button>
            </div>
        </form>
    </div>
</div>

<script>
function print_data() {
    window.print();
}

function openAddReceiptModal() {
    const modal = document.getElementById('add_money_rcpt_modal');
    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
}

function closeAddReceiptModal() {
    const modal = document.getElementById('add_money_rcpt_modal');
    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');
}

function openEditReceiptModal(receiptId) {
    const modal = document.getElementById('edit_money_rcpt_modal');
    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');

    fetch(`/admin/receipts/${receiptId}/json`)
        .then(function (response) { return response.json(); })
        .then(function (data) {
            if (!data.success || !data.receipt) {
                throw new Error('Unable to load receipt details.');
            }

            const receipt = data.receipt;
            document.getElementById('edit_money_rc_id').value = receipt.id || '';
            document.getElementById('edit_payment_data_id').value = receipt.id || '';
            document.getElementById('edit_doctor').value = String(receipt.doctor || '');
            document.getElementById('edit_money_reciept_membership_no').value = receipt.membership_no || '';
            document.getElementById('edit_speciliazition').value = String(receipt.speciliazition || '0');
            document.getElementById('edit_total_amount').value = receipt.payment_amount || '';
            document.getElementById('edit_money_reciept_no').value = receipt.money_reciept_no || '';
            document.getElementById('edit_money_reciept_year').value = String(receipt.money_reciept_year || '0');
            document.getElementById('edit_payment_process').value = receipt.payment_process || 'cash';
            document.getElementById('edit_payment_mode').value = receipt.payment_mode || 'Cash';
            document.getElementById('edit_payment_date').value = receipt.payment_date || '';
            document.getElementById('edit_cheque_no').value = receipt.cheque_no || '';
            document.getElementById('edit_payment_bank').value = receipt.payment_bank || '';
            document.getElementById('edit_payment_branch').value = receipt.payment_branch || '';
            document.getElementById('edit_transaction_no').value = receipt.transaction_no || '';
            document.getElementById('edit_money_remarks').value = receipt.money_remarks || '';

            edit_change_payment_process();
        })
        .catch(function (error) {
            closeEditReceiptModal();
            alert(error.message || 'Failed to open edit modal.');
        });
}

function closeEditReceiptModal() {
    const modal = document.getElementById('edit_money_rcpt_modal');
    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');
}

function edit_doctor_select(doctorId) {
    if (!doctorId) {
        document.getElementById('edit_money_reciept_membership_no').value = '';
        return;
    }

    fetch(`/admin/receipts/doctor/${doctorId}`)
        .then(function (response) { return response.json(); })
        .then(function (data) {
            if (!data.success) {
                return;
            }

            const doctor = data.doctor || {};
            document.getElementById('edit_money_reciept_membership_no').value = doctor.customer_id_no || '';
            if (doctor.specialization_id) {
                document.getElementById('edit_speciliazition').value = String(doctor.specialization_id);
            }
            if (doctor.plan) {
                document.getElementById('plan').value = String(doctor.plan);
            }
            if (doctor.payment_amount) {
                document.getElementById('edit_total_amount').value = doctor.payment_amount;
            }
        })
        .catch(function () {
            alert('Unable to fetch doctor details.');
        });
}

function edit_onchange_spec() {
    // Reserved for legacy parity hooks.
}

function edit_onchange_payment_mode() {
    const mode = document.getElementById('edit_payment_mode').value;
    if (mode === 'Cash') {
        document.getElementById('edit_payment_process').value = 'cash';
    } else if (mode === 'Cheque') {
        document.getElementById('edit_payment_process').value = 'cheque';
    } else {
        document.getElementById('edit_payment_process').value = 'Online';
    }
    edit_change_payment_process();
}

function edit_change_payment_process() {
    const paymentProcess = document.getElementById('edit_payment_process').value;
    if (paymentProcess === 'cheque') {
        document.getElementById('edit_appear_check_payment').style.display = 'grid';
        document.getElementById('edit_appear_online_payment').style.display = 'none';
    } else if (paymentProcess === 'Online') {
        document.getElementById('edit_appear_online_payment').style.display = 'grid';
        document.getElementById('edit_appear_check_payment').style.display = 'none';
    } else {
        document.getElementById('edit_appear_check_payment').style.display = 'none';
        document.getElementById('edit_appear_online_payment').style.display = 'none';
    }
}

function renew_edit_doctor_validation() {
    const doctor = document.getElementById('edit_doctor').value;
    const paymentMode = document.getElementById('edit_payment_mode').value;
    const paymentAmount = document.getElementById('edit_total_amount').value;
    const receiptNo = document.getElementById('edit_money_reciept_no').value;

    if (!doctor) {
        alert('Please select doctor.');
        return false;
    }
    if (!paymentMode) {
        alert('Please select payment mode.');
        return false;
    }
    if (!receiptNo) {
        alert('Please enter money receipt number.');
        return false;
    }
    if (!paymentAmount || Number(paymentAmount) <= 0) {
        alert('Please enter valid amount.');
        return false;
    }

    return true;
}

function doctor_select(doctorId) {
    if (!doctorId) {
        document.getElementById('money_reciept_membership_no').value = '';
        return;
    }

    fetch(`/admin/receipts/doctor/${doctorId}`)
        .then(function (response) { return response.json(); })
        .then(function (data) {
            if (!data.success) {
                return;
            }

            const doctor = data.doctor || {};
            document.getElementById('money_reciept_membership_no').value = doctor.customer_id_no || '';
            if (doctor.specialization_id) {
                document.getElementById('speciliazition').value = String(doctor.specialization_id);
            }
            if (doctor.plan) {
                document.getElementById('plan').value = String(doctor.plan);
                change_plan_id_to_name();
            }
            if (doctor.coverage_id) {
                document.getElementById('coverage').value = String(doctor.coverage_id);
            }
            if (doctor.service_amount) {
                document.getElementById('pre_amount').value = doctor.service_amount;
                document.getElementById('pre_amount_label').style.display = 'block';
            }
            if (doctor.payment_amount) {
                document.getElementById('payment_amount').value = doctor.payment_amount;
            }
        })
        .catch(function () {
            alert('Unable to fetch doctor details.');
        });
}

function onchange_spec() {
    chng_plan(document.getElementById('plan').value);
}

function onchange_payment_mode() {
    const mode = document.getElementById('payment_mode').value;
    if (!mode) {
        return;
    }
    if (mode === 'Cash') {
        document.getElementById('payment_process').value = 'cash';
    } else if (mode === 'Cheque') {
        document.getElementById('payment_process').value = 'cheque';
    } else if (mode === 'Online') {
        document.getElementById('payment_process').value = 'Online';
    }
    change_payment_process();
}

function change_plan_id_to_name() {
    const planSelect = document.getElementById('plan');
    const planName = planSelect.options[planSelect.selectedIndex] ? planSelect.options[planSelect.selectedIndex].text : '';
    document.getElementById('plan_name').value = planName;
}

function chng_plan(planId) {
    const specializationId = document.getElementById('speciliazition').value;
    if (!planId || planId === '0' || !specializationId || specializationId === '0') {
        return false;
    }

    fetch(`/admin/ajax/coverage?plan=${encodeURIComponent(planId)}&specialization_id=${encodeURIComponent(specializationId)}`)
        .then(function (response) { return response.json(); })
        .then(function (options) {
            const coverageSelect = document.getElementById('coverage');
            coverageSelect.innerHTML = '<option value="">--Select Coverage--</option>';

            options.forEach(function (option) {
                const opt = document.createElement('option');
                opt.value = option.id;
                opt.textContent = `${option.name} (Rs. ${option.amount})`;
                opt.dataset.amount = option.amount;
                coverageSelect.appendChild(opt);
            });

            document.getElementById('pre_amount_label').style.display = options.length ? 'block' : 'none';
        })
        .catch(function () {
            alert('Unable to load coverage options.');
        });

    return false;
}

document.addEventListener('change', function (event) {
    if (event.target && event.target.id === 'coverage') {
        const option = event.target.options[event.target.selectedIndex];
        const amount = option ? option.dataset.amount : '';
        document.getElementById('pre_amount').value = amount || '';
        const paymentAmount = parseFloat(document.getElementById('payment_amount').value || 0);
        const serviceAmount = parseFloat(amount || 0);
        const totalAmount = serviceAmount + paymentAmount;
        if (totalAmount > 0) {
            document.getElementById('total_amount').value = totalAmount.toFixed(2);
            document.getElementById('total_amount_label').style.display = 'block';
        }
    }

    if (event.target && event.target.id === 'payment_amount') {
        const serviceAmount = parseFloat(document.getElementById('pre_amount').value || 0);
        const paymentAmount = parseFloat(event.target.value || 0);
        const totalAmount = serviceAmount + paymentAmount;
        if (totalAmount > 0) {
            document.getElementById('total_amount').value = totalAmount.toFixed(2);
            document.getElementById('total_amount_label').style.display = 'block';
        }
    }
});

function change_payment_process() {
    const paymentProcess = document.getElementById('payment_process').value;
    if (paymentProcess === 'cheque') {
        document.getElementById('appear_check_payment').style.display = 'grid';
        document.getElementById('appear_online_payment').style.display = 'none';
    } else if (paymentProcess === 'Online') {
        document.getElementById('appear_online_payment').style.display = 'grid';
        document.getElementById('appear_check_payment').style.display = 'none';
    } else {
        document.getElementById('appear_check_payment').style.display = 'none';
        document.getElementById('appear_online_payment').style.display = 'none';
    }
}

function renew_doctor_validation() {
    const doctor = document.getElementById('doctor').value;
    const plan = document.getElementById('plan').value;
    const paymentMode = document.getElementById('payment_mode').value;
    const paymentAmount = document.getElementById('payment_amount').value;
    const receiptNo = document.getElementById('money_reciept_no').value;

    if (!doctor) {
        alert('Please select doctor.');
        return false;
    }
    if (!paymentMode) {
        alert('Please select payment mode.');
        return false;
    }
    if (!plan || plan === '0') {
        alert('Please select plan.');
        return false;
    }
    if (!receiptNo) {
        alert('Please enter money receipt number.');
        return false;
    }
    if (!paymentAmount || Number(paymentAmount) <= 0) {
        alert('Please enter valid Medeforum amount.');
        return false;
    }

    return true;
}

function resend_money_receipt(doctorId, email) {
    if (!email) {
        alert('No email address on file for this doctor.');
        return;
    }

    fetch(`/admin/doctors/${doctorId}/resend-receipt`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json'
        }
    })
    .then(function (response) { return response.json(); })
    .then(function (data) { alert(data.message); })
    .catch(function (error) { alert('Error: ' + error.message); });
}

document.addEventListener('click', function (event) {
    if (event.target && event.target.id === 'edit_money_rcpt_modal') {
        closeEditReceiptModal();
    }
    if (event.target && event.target.id === 'add_money_rcpt_modal') {
        closeAddReceiptModal();
    }
});
</script>
@endsection

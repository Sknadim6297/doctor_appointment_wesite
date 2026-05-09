@extends('admin.layouts.app')

@section('title', 'Doctor List')
@section('page-title', 'Doctor Management')

@section('content')
<style>
    .doctor-toolbar {
        background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);
        border: 1px solid #dbeafe;
    }
    .doctor-btn {
        display: inline-flex;
        align-items: center;
        border-radius: 0.5rem;
        padding: 0.5rem 0.75rem;
        font-size: 0.8125rem;
        font-weight: 700;
        color: #fff;
        transition: all 0.15s ease;
    }
    .doctor-btn:hover { filter: brightness(0.95); }
    .doctor-btn-blue { background: #1d4ed8; }
    .doctor-btn-amber { background: #d97706; }
    .doctor-heading {
        color: #0f172a;
        letter-spacing: 0.01em;
    }
    .doctor-table {
        border-collapse: separate;
        border-spacing: 0;
        border: 1px solid #dbe3ee;
        border-radius: 0.85rem;
        overflow: visible;
    }
    .doctor-table thead th {
        background: #0f172a;
        color: #e2e8f0;
        font-size: 0.74rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        padding: 0.75rem;
        border-bottom: 1px solid #1e293b;
    }
    .doctor-table tbody td {
        vertical-align: middle;
        border-bottom: 1px solid #e2e8f0;
        color: #0f172a;
        font-size: 0.82rem;
        padding: 0.75rem;
    }
    .doctor-table tbody tr:nth-child(even) { background: #f8fafc; }
    .doctor-table tbody tr:hover { background: #eef6ff; }
    .doctor-pill {
        display: inline-flex;
        align-items: center;
        border-radius: 9999px;
        padding: 0.25rem 0.6rem;
        font-size: 0.7rem;
        font-weight: 700;
        line-height: 1;
    }
    .doctor-pill-renewal { background: #dbeafe; color: #0369a1; }
    .doctor-pill-upcoming { background: #fef08a; color: #78350f; }
    .doctor-pill-due { background: #fecaca; color: #7c2d12; }
    .doctor-action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.35rem;
        padding: 0.35rem 0.5rem;
        color: #fff;
        font-size: 0.75rem;
        font-weight: 700;
        text-decoration: none;
        transition: all 0.2s ease;
        border: none;
        cursor: pointer;
    }
    .doctor-action-btn:hover { transform: translateY(-2px); }
    .doctor-action-btn-view { background: #10b981; }
    .doctor-action-btn-edit { background: #6b7280; }
    .doctor-action-btn-doc { background: #3b82f6; }
    .doctor-action-btn-renew { background: #ef4444; }
    .doctor-action-btn-mail { background: #06b6d4; }
    .doctor-action-btn-sms { background: #0ea5e9; }
    .doctor-action-btn-bond { background: #22c55e; }
    .doctor-action-btn-receipt { background: #8b5cf6; }
    .doctor-action-btn { min-width: 36px; height: 34px; padding: 0.25rem; }
    .doctor-table td.actions-cell { overflow: visible; white-space: nowrap; }
</style>

<div class="section-card">
    {{-- Search & Filter Toolbar --}}
    <div class="doctor-toolbar mb-5 rounded-xl p-4">
        <form method="GET" action="{{ route('admin.doctors.index') }}" id="search_form" class="space-y-3">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-12">
                <div class="md:col-span-3">
                    <input type="text" name="search" placeholder="Search by name, email, phone..." 
                           value="{{ request('search') }}" 
                           class="w-full rounded-lg border-slate-300 text-sm">
                </div>
                <div class="md:col-span-2">
                    <select name="specialization_id" class="w-full rounded-lg border-slate-300 text-sm">
                        <option value="">— All Specializations —</option>
                        @foreach($specializations as $spec)
                            <option value="{{ $spec->id }}" {{ request('specialization_id') == $spec->id ? 'selected' : '' }}>
                                {{ $spec->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <select name="plan" class="w-full rounded-lg border-slate-300 text-sm">
                        <option value="">— All Plans —</option>
                        @foreach($plans as $id => $name)
                            <option value="{{ $id }}" {{ request('plan') == $id ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <select name="renewal_status" class="w-full rounded-lg border-slate-300 text-sm">
                        <option value="">— Renewal Status —</option>
                        <option value="upcoming" {{ request('renewal_status') === 'upcoming' ? 'selected' : '' }}>Upcoming</option>
                        <option value="overdue" {{ request('renewal_status') === 'overdue' ? 'selected' : '' }}>Overdue</option>
                    </select>
                </div>
                <div class="md:col-span-3 flex gap-2">
                    <button type="submit" class="btn-brand !px-4 !py-2 text-sm">Search</button>
                    <a href="{{ route('admin.doctors.index') }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Reset</a>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.enrollment.create') }}" class="doctor-btn doctor-btn-blue">
                    <i class="ri-user-add-line mr-1"></i>
                    New Enrollment
                </a>
                <a href="{{ route('admin.doctors.incomplete-documents') }}" class="doctor-btn doctor-btn-amber">
                    <i class="ri-file-warning-line mr-1"></i>
                    Incomplete Docs
                </a>
            </div>
        </form>
    </div>

    {{-- Page Title & Success Message --}}
    <div class="mb-3 flex items-center justify-between">
        <h3 class="doctor-heading section-title mb-0">Doctor List ({{ $doctors->total() }} total)</h3>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    {{-- Doctor Table --}}
    <div class="overflow-x-auto">
        <table class="doctor-table w-full">
            <thead>
                <tr>
                    <th style="width: 50px;">SL</th>
                    <th>Name / Phone</th>
                    <th>Specialization</th>
                    <th>Plan</th>
                    <th>Membership No</th>
                    <th>Insurance Coverage</th>
                    <th>Premium</th>
                    <th>Collection Date</th>
                    <th>Status</th>
                    <th>Email / SMS</th>
                    <th class="actions-col" style="min-width: 340px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($doctors as $doctor)
                    @php
                        $renewalDate = $doctor->created_at->copy()->addYear();
                        $daysUntilRenewal = now()->diffInDays($renewalDate, false);
                    @endphp
                    <tr>
                        <td class="font-semibold">{{ $doctors->firstItem() + $loop->index }}</td>
                        <td class="actions-cell">
                            <div class="font-semibold text-slate-800">{{ $doctor->doctor_name ?? '—' }}</div>
                            <div class="text-xs text-slate-500">{{ $doctor->mobile1 ?? '—' }}</div>
                        </td>
                        <td>
                            <div class="text-sm">{{ $doctor->specialization->name ?? '—' }}</div>
                        </td>
                        <td>
                            <div class="text-sm">
                                @switch($doctor->plan)
                                    @case('1')
                                        <span class="doctor-pill doctor-pill-renewal">Normal</span>
                                        @break
                                    @case('2')
                                        <span class="doctor-pill doctor-pill-upcoming">High Risk</span>
                                        @break
                                    @case('3')
                                        <span class="doctor-pill doctor-pill-due">Combo</span>
                                        @break
                                    @default
                                        —
                                @endswitch
                            </div>
                        </td>
                        <td>
                            <div class="text-sm font-mono">{{ $doctor->customer_id_no ?? '—' }}</div>
                        </td>
                        <td>
                            <div class="text-sm font-semibold">₹{{ number_format($doctor->payment_amount, 0) ?? '—' }}</div>
                        </td>
                        <td>
                            <div class="text-sm font-semibold">₹{{ number_format($doctor->service_amount, 0) ?? '—' }}</div>
                        </td>
                        <td>
                            <div class="text-sm">{{ $renewalDate->format('d M Y') }}</div>
                        </td>
                        <td>
                            @if($daysUntilRenewal > 30)
                                <span class="doctor-pill doctor-pill-renewal">Upcoming</span>
                            @elseif($daysUntilRenewal > 0)
                                <span class="doctor-pill doctor-pill-upcoming">Due Soon</span>
                            @else
                                <span class="doctor-pill doctor-pill-due">Overdue</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex gap-1">
                                <li style="color: {{ $doctor->bond_to_mail ? 'green' : 'red' }}; font-size: 1.2rem;" 
                                    class="ri-mail-line" 
                                    title="{{ $doctor->bond_to_mail ? 'Auto email enabled' : 'Auto email disabled' }}"></li>
                            </div>
                        </td>
                        <td class="actions-cell">
                            <div class="flex flex-wrap gap-1">
                                <a href="{{ route('admin.doctors.show', $doctor->id) }}" class="doctor-action-btn doctor-action-btn-view" title="View Details" onclick="event.stopPropagation();">
                                    <i class="ri-eye-line"></i>
                                </a>
                                <a href="{{ route('admin.enrollment.legacy-edit', $doctor->id) }}" class="doctor-action-btn doctor-action-btn-edit" title="Edit" onclick="event.stopPropagation();">
                                    <i class="ri-pencil-line"></i>
                                </a>
                                <a target="_blank" href="{{ route('admin.doctors.show', $doctor->id) }}?tab=doctor_documents" class="doctor-action-btn doctor-action-btn-doc" title="Document" onclick="event.stopPropagation();">
                                    <i class="ri-file-line"></i>
                                </a>
                                <button type="button" class="doctor-action-btn doctor-action-btn-renew" title="Renew" onclick="event.stopPropagation(); renewDoctor({{ $doctor->id }})">
                                    <i class="ri-refresh-line"></i>
                                </button>
                                <button type="button" class="doctor-action-btn doctor-action-btn-mail" title="Send Email" onclick="event.stopPropagation(); sendMail({{ $doctor->id }}, '{{ $doctor->doctor_email }}')">
                                    <i class="ri-mail-line"></i>
                                </button>
                                <button type="button" class="doctor-action-btn doctor-action-btn-sms" title="Send SMS" onclick="event.stopPropagation(); sendSms({{ $doctor->id }}, '{{ $doctor->mobile1 }}')">
                                    <i class="ri-message-2-line"></i>
                                </button>
                                <button type="button" class="doctor-action-btn doctor-action-btn-bond" title="Resend Bond" onclick="event.stopPropagation(); resendBond({{ $doctor->id }}, '{{ $doctor->doctor_email }}')">
                                    <i class="ri-send-plane-line"></i>
                                </button>
                                <button type="button" class="doctor-action-btn doctor-action-btn-receipt" title="Resend Receipt" onclick="event.stopPropagation(); resendReceipt({{ $doctor->id }}, '{{ $doctor->doctor_email }}')">
                                    <i class="ri-mail-send-line"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="text-center py-8 text-slate-500">
                            No doctors found. <a href="{{ route('admin.enrollment.create') }}" class="text-blue-600 hover:underline">Create a new enrollment</a>.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($doctors->hasPages())
        <div class="mt-6">{{ $doctors->links() }}</div>
    @endif
</div>

<script>
const csrfToken = '{{ csrf_token() }}';

/**
 * Open the Add Money Receipt modal and prefill it for renewing the selected doctor.
 * Uses the existing `add_money_rcpt_modal` present in receipts view markup.
 */
function renewDoctor(doctorId) {
    // find modal and form fields from receipts view
    const modal = document.getElementById('add_money_rcpt_modal');
    if (!modal) {
        // fallback: navigate to legacy renewal URL if modal not present
        if (confirm('Renewal form not available inline. Open legacy renewal page?')) {
            window.location.href = '/admin/index.php/renewal_list/renew_action/' + doctorId + '?ref=0';
        }
        return;
    }

    // show modal
    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');

    // Clear policy_no field for new renewal
    document.getElementById('policy_no').value = '';

    // set doctor select and trigger dependent data load
    const doctorSelect = document.getElementById('doctor');
    if (doctorSelect) {
        doctorSelect.value = String(doctorId);
        // try to run the existing helper to fetch doctor details
        if (typeof doctor_select === 'function') {
            doctor_select(doctorId);
        } else {
            // attempt to load minimal data via fetch
            fetch(`/admin/receipts/doctor/${doctorId}`)
                .then(r => r.json())
                .then(data => {
                    if (!data.success) return;
                    const d = data.doctor || {};
                    const membership = document.getElementById('money_reciept_membership_no');
                    if (membership) membership.value = d.customer_id_no || '';
                    // Keep policy_no blank
                    document.getElementById('policy_no').value = '';
                    if (d.plan) {
                        const planEl = document.getElementById('plan');
                        planEl.value = String(d.plan);
                        if (typeof change_plan_id_to_name === 'function') change_plan_id_to_name();
                        if (typeof chng_plan === 'function') chng_plan(d.plan);
                    }
                    if (d.service_amount) {
                        const pre = document.getElementById('pre_amount');
                        if (pre) pre.value = d.service_amount;
                        const preLabel = document.getElementById('pre_amount_label');
                        if (preLabel) preLabel.style.display = 'block';
                    }
                    if (d.payment_amount) {
                        const pay = document.getElementById('payment_amount');
                        if (pay) pay.value = d.payment_amount;
                    }
                })
                .catch(() => {});
        }
    }

    // ensure money receipt is marked for renewal
    const rcFor = document.getElementById('money_rc_for');
    if (rcFor) rcFor.value = 'renewal';

    // focus money receipt number field for quick entry
    const receiptNo = document.getElementById('money_reciept_no');
    if (receiptNo) receiptNo.focus();
}

function sendMail(doctorId, email) {
    if (!email) {
        alert('No email address on file for this doctor.');
        return;
    }
    fetch(`/admin/doctors/${doctorId}/send-mail`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ email })
    })
    .then(r => r.json())
    .then(data => {
        if (data && data.message) alert(data.message);
        else alert('Email send request completed.');
    })
    .catch(err => alert('Error: ' + (err.message || err)));
}

function sendSms(doctorId, phone) {
    if (!phone) {
        alert('No phone number on file for this doctor.');
        return;
    }
    fetch(`/admin/doctors/${doctorId}/send-sms`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ phone })
    })
    .then(r => r.json())
    .then(data => {
        if (data && data.message) alert(data.message);
        else alert('SMS send request completed.');
    })
    .catch(err => alert('Error: ' + (err.message || err)));
}

function resendBond(doctorId, email) {
    if (!email) {
        alert('No email address on file for this doctor.');
        return;
    }
    fetch(`/admin/doctors/${doctorId}/resend-bond`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ email })
    })
    .then(r => r.json())
    .then(data => {
        if (data && data.message) alert(data.message);
        else alert('Resend bond request completed.');
    })
    .catch(err => alert('Error: ' + (err.message || err)));
}

function resendReceipt(doctorId, email) {
    if (!email) {
        alert('No email address on file for this doctor.');
        return;
    }
    fetch(`/admin/doctors/${doctorId}/resend-receipt`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ email })
    })
    .then(r => r.json())
    .then(data => {
        if (data && data.message) alert(data.message);
        else alert('Resend receipt request completed.');
    })
    .catch(err => alert('Error: ' + (err.message || err)));
}
</script>
<!-- Add Money Receipt modal (copied from receipts view) to support inline renewals -->
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
                        <label for="policy_no">Policy No.</label>
                        <input type="text" id="policy_no" name="policy_no" placeholder="Leave blank for new policy">
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
function print_data() { window.print(); }

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

function doctor_select(doctorId) {
    if (!doctorId) {
        document.getElementById('money_reciept_membership_no').value = '';
        document.getElementById('policy_no').value = '';
        return;
    }

    fetch(`/admin/receipts/doctor/${doctorId}`)
        .then(function (response) { return response.json(); })
        .then(function (data) {
            if (!data.success) return;

            const doctor = data.doctor || {};
            document.getElementById('money_reciept_membership_no').value = doctor.customer_id_no || '';
            // Keep policy_no blank for new enrollment/renewal
            document.getElementById('policy_no').value = '';
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
        .catch(function () { alert('Unable to fetch doctor details.'); });
}

function onchange_spec() { chng_plan(document.getElementById('plan').value); }

function onchange_payment_mode() {
    const mode = document.getElementById('payment_mode').value;
    if (!mode) return;
    if (mode === 'Cash') document.getElementById('payment_process').value = 'cash';
    else if (mode === 'Cheque') document.getElementById('payment_process').value = 'cheque';
    else if (mode === 'Online') document.getElementById('payment_process').value = 'Online';
    change_payment_process();
}

function change_plan_id_to_name() {
    const planSelect = document.getElementById('plan');
    const planName = planSelect.options[planSelect.selectedIndex] ? planSelect.options[planSelect.selectedIndex].text : '';
    document.getElementById('plan_name').value = planName;
}

function chng_plan(planId) {
    const specializationId = document.getElementById('speciliazition').value;
    if (!planId || planId === '0' || !specializationId || specializationId === '0') return false;

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
        .catch(function () { alert('Unable to load coverage options.'); });

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

    if (!doctor) { alert('Please select doctor.'); return false; }
    if (!paymentMode) { alert('Please select payment mode.'); return false; }
    if (!plan || plan === '0') { alert('Please select plan.'); return false; }
    if (!receiptNo) { alert('Please enter money receipt number.'); return false; }
    if (!paymentAmount || Number(paymentAmount) <= 0) { alert('Please enter valid Medeforum amount.'); return false; }

    return true;
}

function resend_money_receipt(doctorId, email) {
    if (!email) { alert('No email address on file for this doctor.'); return; }

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
    if (event.target && event.target.id === 'add_money_rcpt_modal') closeAddReceiptModal();
});
</script>
@endsection

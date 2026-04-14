@extends('admin.layouts.app')

@section('title', 'Edit Money Receipt')
@section('page-title', 'Account Management')

@section('content')
@php
    $receiptNoParts = explode('/', (string) ($receipt->money_rc_no ?? ''));
    $receiptNoBase = $receiptNoParts[0] ?? '';
    $receiptNoYear = $receiptNoParts[1] ?? '';

    if (!empty($receipt->payment_upi_transaction_id)) {
        $paymentProcess = 'Online';
    } elseif (!empty($receipt->payment_cheque) || !empty($receipt->payment_bank_name) || !empty($receipt->payment_branch_name)) {
        $paymentProcess = 'cheque';
    } else {
        $paymentProcess = 'cash';
    }

    $paymentModeValue = match ($paymentProcess) {
        'cheque' => 'Cheque',
        'Online' => 'Online',
        default => 'Cash',
    };
@endphp

<style>
    .receipt-edit-shell {
        background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
        border: 1px solid #dbe3ee;
        border-radius: 1rem;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        padding: 1.25rem;
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
    .receipt-field textarea { min-height: 76px; }
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
    .receipt-btn-save { background: #10b981; color: #fff; }
    .receipt-btn-cancel { background: #475569; color: #fff; }
    .receipt-required { color: #dc2626; }
</style>

<div class="section-card space-y-5">
    <div class="receipt-edit-shell">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <div>
                <h3 class="section-title mb-1">Edit Money Receipt</h3>
                <p class="text-sm text-slate-600">Update receipt data for selected doctor.</p>
            </div>
            <a href="{{ route('admin.receipts') }}" class="receipt-btn receipt-btn-cancel">
                <i class="ri-arrow-left-line"></i>
                Back to receipts
            </a>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.receipts.update', $receipt->id) }}" onsubmit="return renew_doctor_validation();">
            @csrf
            @method('PUT')

            <div class="receipt-form-grid">
                <div class="receipt-field full">
                    <label for="doctor">Select Doctor <span class="receipt-required">*</span></label>
                    <select name="doctor" id="doctor" onchange="doctor_select(this.value);" required>
                        <option value="">--Select doctor--</option>
                        @foreach($doctors as $doctor)
                            <option value="{{ $doctor->id }}" {{ (int) old('doctor', $receipt->id) === (int) $doctor->id ? 'selected' : '' }}>{{ $doctor->doctor_name }}{{ $doctor->money_rc_no ? ' (' . $doctor->money_rc_no . ')' : '' }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="receipt-field">
                    <label for="money_reciept_membership_no">Membership no.</label>
                    <input type="text" id="money_reciept_membership_no" name="money_reciept_membership_no" value="{{ old('money_reciept_membership_no', $receipt->customer_id_no) }}" readonly>
                </div>

                <div class="receipt-field">
                    <label for="speciliazition">Specialization</label>
                    <select name="speciliazition" id="speciliazition" onchange="onchange_spec();">
                        <option value="0">---Select specialization---</option>
                        @foreach($specializations as $specialization)
                            <option value="{{ $specialization->id }}" {{ (int) old('speciliazition', $receipt->specialization_id) === (int) $specialization->id ? 'selected' : '' }}>{{ $specialization->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="receipt-field">
                    <label for="payment_mode">Payment Mode <span class="receipt-required">*</span></label>
                    <select name="payment_mode" id="payment_mode" onchange="onchange_payment_mode();" required>
                        <option value="">---Select Payment Mode---</option>
                        <option value="Cash" {{ old('payment_mode', $paymentModeValue) === 'Cash' ? 'selected' : '' }}>Cash</option>
                        <option value="Cheque" {{ old('payment_mode', $paymentModeValue) === 'Cheque' ? 'selected' : '' }}>Cheque</option>
                        <option value="Online" {{ old('payment_mode', $paymentModeValue) === 'Online' ? 'selected' : '' }}>Online</option>
                    </select>
                </div>

                <div class="receipt-field">
                    <label for="plan">Plan <span class="receipt-required">*</span></label>
                    <input type="hidden" name="plan_name" id="plan_name" value="{{ old('plan_name', $receipt->plan_name) }}">
                    <select name="plan" id="plan" onchange="change_plan_id_to_name(); return chng_plan(this.value);" required>
                        <option value="0">---Select plan---</option>
                        <option value="1" {{ (int) old('plan', $receipt->plan) === 1 ? 'selected' : '' }}>Normal</option>
                        <option value="2" {{ (int) old('plan', $receipt->plan) === 2 ? 'selected' : '' }}>High Risk</option>
                        <option value="3" {{ (int) old('plan', $receipt->plan) === 3 ? 'selected' : '' }}>Combo</option>
                    </select>
                </div>

                <div class="receipt-field">
                    <label for="coverage" id="coverage_text">Legal Service</label>
                    <select name="coverage" id="coverage">
                        <option value="{{ old('coverage', $receipt->coverage_id) }}" selected>{{ old('coverage', $receipt->coverage_id) ? 'Current Coverage #' . old('coverage', $receipt->coverage_id) : '--Select Coverage--' }}</option>
                    </select>
                </div>

                <div class="receipt-field" id="pre_amount_label" style="display:{{ (float) old('service_amount', $receipt->service_amount) > 0 ? 'block' : 'none' }};">
                    <label for="pre_amount">Insurance Amount</label>
                    <input type="text" name="service_amount" id="pre_amount" value="{{ old('service_amount', $receipt->service_amount) }}" readonly>
                </div>

                <div class="receipt-field">
                    <label for="payment_amount">Medeforum Amount <span class="receipt-required">*</span></label>
                    <input type="number" min="0" step="0.01" name="payment_amount" id="payment_amount" value="{{ old('payment_amount', $receipt->payment_amount) }}" required>
                </div>

                <div class="receipt-field" id="total_amount_label" style="display:{{ (float) old('total_amount', $receipt->total_amount) > 0 ? 'block' : 'none' }};">
                    <label for="total_amount">Total Amount</label>
                    <input type="text" name="total_amount" id="total_amount" value="{{ old('total_amount', $receipt->total_amount) }}" readonly>
                </div>

                <div class="receipt-field">
                    <label for="money_reciept_no">Money receipt no. <span class="receipt-required">*</span></label>
                    <input type="text" name="money_reciept_no" id="money_reciept_no" value="{{ old('money_reciept_no', $receiptNoBase) }}" required>
                </div>

                <div class="receipt-field">
                    <label for="money_reciept_year">Money receipt year</label>
                    <select name="money_reciept_year" id="money_reciept_year">
                        <option value="0">---Select Year---</option>
                        @foreach($years as $year)
                            <option value="{{ $year }}" {{ (string) old('money_reciept_year', $receiptNoYear) === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="receipt-field">
                    <label for="payment_process">Payment process</label>
                    <select name="payment_process" id="payment_process" onchange="change_payment_process();">
                        <option value="cash" {{ old('payment_process', $paymentProcess) === 'cash' ? 'selected' : '' }}>Cash</option>
                        <option value="cheque" {{ old('payment_process', $paymentProcess) === 'cheque' ? 'selected' : '' }}>Cheque</option>
                        <option value="Online" {{ old('payment_process', $paymentProcess) === 'Online' ? 'selected' : '' }}>Online</option>
                    </select>
                </div>

                <div class="receipt-field">
                    <label for="payment_date">Payment date</label>
                    <input type="date" name="payment_date" id="payment_date" value="{{ old('payment_date', optional($receipt->payment_cash_date)->format('Y-m-d')) }}">
                </div>

                <div id="appear_check_payment" class="full receipt-form-grid" style="display:none; grid-template-columns: repeat(1, minmax(0, 1fr));">
                    <div class="receipt-field">
                        <label for="cheque_no">Cheque no.</label>
                        <input type="text" name="cheque_no" id="cheque_no" value="{{ old('cheque_no', $receipt->payment_cheque) }}">
                    </div>
                    <div class="receipt-field">
                        <label for="payment_bank">Bank</label>
                        <input type="text" name="payment_bank" id="payment_bank" value="{{ old('payment_bank', $receipt->payment_bank_name) }}">
                    </div>
                    <div class="receipt-field">
                        <label for="payment_branch">Branch</label>
                        <input type="text" name="payment_branch" id="payment_branch" value="{{ old('payment_branch', $receipt->payment_branch_name) }}">
                    </div>
                </div>

                <div id="appear_online_payment" class="full receipt-form-grid" style="display:none; grid-template-columns: repeat(1, minmax(0, 1fr));">
                    <div class="receipt-field">
                        <label for="transaction_no">Transaction no.</label>
                        <input type="text" name="transaction_no" id="transaction_no" value="{{ old('transaction_no', $receipt->payment_upi_transaction_id) }}">
                    </div>
                </div>

                <div class="receipt-field">
                    <label for="money_rc_for">Money receipt for</label>
                    <select name="money_rc_for" id="money_rc_for">
                        <option value="renewal" {{ old('money_rc_for') === 'renewal' ? 'selected' : '' }}>Renewal</option>
                        <option value="enrollment" {{ old('money_rc_for') === 'enrollment' ? 'selected' : '' }}>Enrollment</option>
                    </select>
                </div>

                <div class="receipt-field full">
                    <label for="money_remarks">Remarks</label>
                    <textarea name="money_remarks" id="money_remarks" placeholder="Give remarks">{{ old('money_remarks') }}</textarea>
                </div>

                <div class="full flex justify-end gap-2 pt-2">
                    <a href="{{ route('admin.receipts') }}" class="receipt-btn receipt-btn-cancel">Cancel</a>
                    <button type="submit" class="receipt-btn receipt-btn-save">Update Receipt</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
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
            recalculate_total();
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
            const currentValue = coverageSelect.value;

            coverageSelect.innerHTML = '<option value="">--Select Coverage--</option>';

            options.forEach(function (option) {
                const opt = document.createElement('option');
                opt.value = option.id;
                opt.textContent = `${option.name} (Rs. ${option.amount})`;
                opt.dataset.amount = option.amount;
                if (String(option.id) === String(currentValue)) {
                    opt.selected = true;
                }
                coverageSelect.appendChild(opt);
            });

            document.getElementById('pre_amount_label').style.display = options.length ? 'block' : 'none';
            recalculate_total();
        })
        .catch(function () {
            alert('Unable to load coverage options.');
        });

    return false;
}

function recalculate_total() {
    const paymentAmount = parseFloat(document.getElementById('payment_amount').value || 0);
    const serviceAmount = parseFloat(document.getElementById('pre_amount').value || 0);
    const totalAmount = serviceAmount + paymentAmount;

    if (totalAmount > 0) {
        document.getElementById('total_amount').value = totalAmount.toFixed(2);
        document.getElementById('total_amount_label').style.display = 'block';
    }
}

document.addEventListener('change', function (event) {
    if (event.target && event.target.id === 'coverage') {
        const option = event.target.options[event.target.selectedIndex];
        const amount = option ? option.dataset.amount : '';
        document.getElementById('pre_amount').value = amount || document.getElementById('pre_amount').value;
        recalculate_total();
    }

    if (event.target && event.target.id === 'payment_amount') {
        recalculate_total();
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

change_plan_id_to_name();
change_payment_process();
</script>
@endsection

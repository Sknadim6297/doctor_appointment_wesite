@extends('admin.layouts.app')

@section('title', 'Renewal Cheque Deposit')
@section('page-title', 'Account Management')

@section('content')
<style>
    .receipt-shell { background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%); border: 1px solid #dbe3ee; border-radius: 1rem; box-shadow: 0 1px 2px rgba(15,23,42,0.04); }
    .receipt-filter { background:#fff; border:1px solid #dbe3ee; border-radius:0.95rem; padding:1rem; }
    .receipt-btn { display:inline-flex; align-items:center; gap:0.35rem; border-radius:0.55rem; padding:0.5rem 0.8rem; font-size:0.78rem; font-weight:700; border:0; cursor:pointer; }
    .receipt-btn-view { background:#10b981; color:#fff; }
    .receipt-btn-doc { background:#2563eb; color:#fff; }
    .receipt-btn-edit { background:#f59e0b; color:#fff; }
    .receipt-table { border-collapse: separate; border-spacing:0; border:1px solid #dbe3ee; border-radius:0.95rem; overflow:hidden; min-width:1200px; }
    .receipt-table thead th { background:#0f172a; color:#e2e8f0; font-size:0.72rem; font-weight:800; padding:0.8rem 0.7rem; }
    .receipt-table tbody td { font-size:0.85rem; padding:0.7rem; border-bottom:1px solid #e2e8f0; }
    .receipt-modal-backdrop { position: fixed; inset: 0; z-index: 60; background: rgba(15, 23, 42, 0.45); display: none; align-items: center; justify-content: center; padding: 1rem; }
    .receipt-modal-backdrop.show { display: flex; }
    .receipt-modal { width: min(980px, 96vw); max-height: 92vh; overflow-y: auto; background: #fff; border-radius: 1rem; border: 1px solid #dbe3ee; box-shadow: 0 25px 50px -12px rgba(15,23,42,.3); }
    .modal-head { display:flex; align-items:center; justify-content:space-between; padding: .9rem 1rem; border-bottom: 1px solid #e2e8f0; }
    .modal-body { padding: 1rem; }
    .modal-foot { display:flex; justify-content:flex-end; gap:.5rem; padding: .9rem 1rem; border-top: 1px solid #e2e8f0; }
    .receipt-form-grid { display:grid; grid-template-columns: repeat(1, minmax(0,1fr)); gap:.8rem; }
    @media (min-width: 768px) { .receipt-form-grid { grid-template-columns: repeat(2, minmax(0,1fr)); } }
    .receipt-field.full { grid-column: 1 / -1; }
    .receipt-field label { display:block; margin-bottom: .3rem; font-size:.74rem; font-weight:700; color:#475569; text-transform: uppercase; letter-spacing:.03em; }
    .receipt-field input, .receipt-field select, .receipt-field textarea { width:100%; border:1px solid #cbd5e1; border-radius:.6rem; padding:.58rem .7rem; font-size:.86rem; }
    .receipt-field textarea { min-height: 92px; resize: vertical; }
    .receipt-required { color: #dc2626; }
    .receipt-text-muted { margin-top:.3rem; font-size:.72rem; color:#64748b; }
    @media print { .no-print { display:none !important; } }
</style>

<div class="section-card space-y-5">
    <div class="receipt-shell p-5 md:p-6">
        <div class="flex items-center justify-between no-print">
            <div>
                <h3 class="section-title mb-2">Renewal cheque deposit ({{ $receipts->total() }})</h3>
                <p class="text-sm text-slate-600">Search month/year, print view and CSV export for renewal cheque deposits.</p>
            </div>
            <div class="flex gap-2">
                <button type="button" class="receipt-btn receipt-btn-view" onclick="print_data()"><i class="ri-printer-line"></i> Print All</button>
                <a href="{{ route('admin.receipts.renewal-cheque-deposit.csv', request()->query()) }}" class="receipt-btn receipt-btn-doc"><i class="ri-file-download-line"></i> Export CSV</a>
                <button type="button" class="receipt-btn receipt-btn-edit" onclick="openAddReceiptModal()"><i class="ri-add-line"></i> + New Cheque Deposit</button>
            </div>
        </div>

        <div class="receipt-filter mt-4 no-print">
            <form method="GET" action="{{ route('admin.receipts.renewal-cheque-deposit') }}">
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
                            <a href="{{ route('admin.receipts.renewal-cheque-deposit') }}" class="receipt-btn receipt-btn-doc">Reset</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="mt-5 overflow-x-auto">
            <table class="receipt-table w-full">
                <thead>
                    <tr>
                        <th style="width:58px;">SL No</th>
                        <th>Doctor</th>
                        <th>Policy No</th>
                        <th>Money Receipt</th>
                        <th>Cheque no.</th>
                        <th>Bank</th>
                        <th>Bank branch</th>
                        <th>Deposit date</th>
                        <th>Amount</th>
                        <th>Payment for</th>
                        <th>Remarks</th>
                        <th style="min-width:100px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($receipts as $receipt)
                        <tr>
                            <td><b>{{ $receipts->firstItem() + $loop->index }}</b></td>
                            <td>
                                @if($receipt->enrollment_id)
                                    <a target="_blank" href="{{ route('admin.doctors.show', $receipt->enrollment_id) }}">{{ $receipt->doctor_name ?? 'N/A' }}</a>
                                @else
                                    {{ $receipt->doctor_name ?? 'N/A' }}
                                @endif
                            </td>
                            <td>{{ $receipt->policy_no ?? 'N/A' }}</td>
                            <td>{{ $receipt->money_reciept_no ?? 'N/A' }}</td>
                            <td>{{ $receipt->cheque_no ?? 'N/A' }}</td>
                            <td>{{ $receipt->bank ?: 'N.A' }}</td>
                            <td>{{ $receipt->bank_branch ?: 'N.A' }}</td>
                            <td>{{ optional($receipt->payment_date ?? $receipt->created_at)->format('d/m/Y') ?? 'N/A' }}</td>
                            <td><b>Rs. {{ number_format((float) ($receipt->cheque_amount ?? 0), 0) }}/-</b></td>
                            <td>Renewal</td>
                            <td>{{ $receipt->remarks ?: 'None' }}</td>
                            <td>
                                <div class="flex gap-1">
                                    <button type="button" class="receipt-btn receipt-btn-edit" title="Edit record" onclick="openEditReceiptModal({{ $receipt->id }})"><i class="ri-pencil-line"></i></button>
                                    <form action="{{ route('admin.receipts.renewal-cheque-deposit.destroy', $receipt->id) }}" method="POST" onsubmit="return confirm('Delete this renewal cheque deposit?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="receipt-btn receipt-btn-doc" title="Delete record"><i class="ri-delete-bin-line"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="py-8 text-center text-slate-500">No renewal cheque deposit records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($receipts->hasPages())
            <div class="mt-4">{{ $receipts->links() }}</div>
        @endif

        <div id="print_content" style="display:none;">
            <h3>Renewal cheque deposit</h3>
            <table class="table table-bordered" width="100%">
                <thead>
                    <tr>
                        <th>SL No</th>
                        <th>Doctor</th>
                        <th>Policy No</th>
                        <th>Money Receipt No</th>
                        <th>Cheque no.</th>
                        <th>Bank</th>
                        <th>Bank branch</th>
                        <th>Deposit date</th>
                        <th>Amount</th>
                        <th>Payment for</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($receipts as $receipt)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $receipt->doctor_name ?? 'N/A' }}</td>
                            <td>{{ $receipt->policy_no ?? 'N/A' }}</td>
                            <td>{{ $receipt->money_reciept_no ?? 'N/A' }}</td>
                            <td>{{ $receipt->cheque_no ?? 'N.A' }}</td>
                            <td>{{ $receipt->bank ?: 'N.A' }}</td>
                            <td>{{ $receipt->bank_branch ?: 'N.A' }}</td>
                            <td>{{ optional($receipt->payment_date ?? $receipt->created_at)->format('d/m/Y') ?? 'N/A' }}</td>
                            <td>Rs. {{ number_format((float) ($receipt->cheque_amount ?? 0), 0) }}/-</td>
                            <td>Renewal</td>
                            <td>{{ $receipt->remarks ?: 'None' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="cheque_deposit_modal" class="receipt-modal-backdrop no-print" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="receipt-modal">
        <div class="modal-head">
            <h4 class="text-base font-extrabold text-slate-800" id="cheque_deposit_modal_title">New Cheque Deposite</h4>
            <button type="button" class="text-slate-400 hover:text-slate-700" onclick="closeChequeDepositModal()"><i class="ri-close-line text-xl"></i></button>
        </div>

        <form action="{{ route('admin.receipts.renewal-cheque-deposit.store') }}" method="POST" enctype="multipart/form-data" id="cheque_deposit_form" onsubmit="return chequeDepositValidation();">
            @csrf
            <input type="hidden" name="receipt_id" id="receipt_id" value="">
            <div class="modal-body space-y-4">
                <div class="receipt-form-grid">
                    <div class="receipt-field full">
                        <label for="doctor">Doctor <span class="receipt-required">*</span></label>
                        <select name="doctor" id="doctor" onchange="doctor_select(this.value);" required>
                            <option value="">--Select doctor--</option>
                            @foreach($doctors as $doctor)
                                <option value="{{ $doctor->id }}">{{ $doctor->doctor_name }}{{ $doctor->money_rc_no ? ' (' . $doctor->money_rc_no . ')' : '' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="receipt-field">
                        <label for="member_no">Member No.</label>
                        <input type="text" name="member_no" id="member_no" readonly>
                    </div>

                    <div class="receipt-field">
                        <label for="policy_no">Policy No.</label>
                        <input type="text" name="policy_no" id="policy_no">
                    </div>

                    <div class="receipt-field">
                        <label for="money_reciept_no">Money Reciept No.</label>
                        <input type="text" name="money_reciept_no" id="money_reciept_no">
                    </div>

                    <div class="receipt-field">
                        <label for="cheque_no">Cheque No.</label>
                        <input type="text" name="cheque_no" id="cheque_no">
                    </div>

                    <div class="receipt-field">
                        <label for="bank">Bank</label>
                        <input type="text" name="bank" id="bank">
                    </div>

                    <div class="receipt-field">
                        <label for="bank_branch">Bank Branch</label>
                        <input type="text" name="bank_branch" id="bank_branch">
                    </div>

                    <div class="receipt-field">
                        <label for="cheque_amount">Cheque Amount</label>
                        <input type="number" min="0" step="0.01" name="cheque_amount" id="cheque_amount">
                    </div>

                    <div class="receipt-field">
                        <label for="payment_date">Payment Date</label>
                        <input type="date" name="payment_date" id="payment_date">
                    </div>

                    <div class="receipt-field full">
                        <label for="chequeFile">Cheque File</label>
                        <input type="file" name="chequeFile" id="chequeFile">
                    </div>

                    <div class="receipt-field full">
                        <label for="remarks">Remarks</label>
                        <textarea name="remarks" id="remarks" placeholder="Give remarks"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="receipt-btn receipt-btn-edit" onclick="closeChequeDepositModal()">Close</button>
                <button type="submit" class="receipt-btn receipt-btn-view" id="cheque_deposit_submit_button">Submit</button>
            </div>
        </form>
    </div>
</div>

<script>
function print_data() {
    var divToPrint = document.getElementById('print_content');
    if (!divToPrint) { window.print(); return; }
    var newWin = window.open('', 'Print-Window');
    newWin.document.open();
    newWin.document.write('<html><body>' + divToPrint.outerHTML + '</body></html>');
    newWin.document.close();
    newWin.print();
    newWin.close();
}

function openEditReceiptModal(receiptId) {
    const form = document.getElementById('cheque_deposit_form');
    resetChequeDepositModal();
    document.getElementById('cheque_deposit_modal_title').textContent = 'Update Cheque Deposite';
    document.getElementById('cheque_deposit_submit_button').textContent = 'Update';
    form.action = @json(route('admin.receipts.renewal-cheque-deposit.update', ['receipt' => '__RECEIPT__'])).replace('__RECEIPT__', receiptId);
    const existingMethodInput = form.querySelector('input[name="_method"]');
    if (existingMethodInput) {
        existingMethodInput.remove();
    }
    const methodInput = document.createElement('input');
    methodInput.type = 'hidden';
    methodInput.name = '_method';
    methodInput.value = 'PUT';
    form.appendChild(methodInput);

    fetch(@json(route('admin.receipts.renewal-cheque-deposit.json', ['receipt' => '__RECEIPT__'])).replace('__RECEIPT__', receiptId))
        .then(function (response) { return response.json(); })
        .then(function (data) {
            if (!data.success || !data.receipt) {
                throw new Error('Unable to load receipt details.');
            }

            const receipt = data.receipt;
            document.getElementById('receipt_id').value = receipt.id || '';
            document.getElementById('doctor').value = String(receipt.doctor || '');
            document.getElementById('member_no').value = receipt.member_no || '';
            document.getElementById('policy_no').value = receipt.policy_no || '';
            document.getElementById('money_reciept_no').value = receipt.money_reciept_no || '';
            document.getElementById('cheque_no').value = receipt.cheque_no || '';
            document.getElementById('bank').value = receipt.bank || '';
            document.getElementById('bank_branch').value = receipt.bank_branch || '';
            document.getElementById('cheque_amount').value = receipt.cheque_amount || '';
            document.getElementById('payment_date').value = receipt.payment_date || '';
            document.getElementById('remarks').value = receipt.remarks || '';

            openChequeDepositModal();
        })
        .catch(function (error) {
            closeChequeDepositModal();
            alert(error.message || 'Failed to open edit modal.');
        });
}

function openAddReceiptModal() {
    resetChequeDepositModal();
    document.getElementById('cheque_deposit_modal_title').textContent = 'New Cheque Deposite';
    document.getElementById('cheque_deposit_submit_button').textContent = 'Submit';
    const form = document.getElementById('cheque_deposit_form');
    form.action = @json(route('admin.receipts.renewal-cheque-deposit.store'));
    const methodInput = form.querySelector('input[name="_method"]');
    if (methodInput) {
        methodInput.remove();
    }
    openChequeDepositModal();
}

function openChequeDepositModal() {
    const modal = document.getElementById('cheque_deposit_modal');
    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
}

function closeChequeDepositModal() {
    const modal = document.getElementById('cheque_deposit_modal');
    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');
}

function doctor_select(doctorId) {
    if (!doctorId) {
        document.getElementById('member_no').value = '';
        return;
    }

    fetch(@json(route('admin.receipts.renewal-cheque-deposit.membership-no')) + '?doctor=' + encodeURIComponent(doctorId))
        .then(function (response) { return response.json(); })
        .then(function (data) {
            if (!data.success) return;
            const doctor = data.doctor || {};
            document.getElementById('member_no').value = doctor.member_no || doctor.customer_id_no || '';
        });
}

function resetChequeDepositModal() {
    const form = document.getElementById('cheque_deposit_form');
    form.reset();
    document.getElementById('receipt_id').value = '';
    document.getElementById('member_no').value = '';
}

function chequeDepositValidation() {
    if (!document.getElementById('doctor').value) {
        alert('Please select doctor.');
        return false;
    }
    if (!document.getElementById('money_reciept_no').value) {
        alert('Please enter money receipt number.');
        return false;
    }
    if (!document.getElementById('cheque_amount').value || Number(document.getElementById('cheque_amount').value) <= 0) {
        alert('Please enter valid amount.');
        return false;
    }
    return true;
}

document.addEventListener('click', function (event) {
    if (event.target && event.target.id === 'cheque_deposit_modal') closeChequeDepositModal();
});
</script>
@endsection

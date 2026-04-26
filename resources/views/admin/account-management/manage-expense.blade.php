@extends('admin.layouts.app')

@section('title', 'Manage Expense')
@section('page-title', 'Account Management')

@section('content')
<section class="section-card">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <h3 class="section-title mb-0">Manage expensive ({{ $expenses->total() }})</h3>
    </div>

    <div class="mb-4 rounded-xl border border-slate-200 bg-slate-50 p-3">
        <form method="GET" action="{{ route('admin.manage-expense.index') }}" class="grid grid-cols-1 gap-3 md:grid-cols-12 md:items-end">
            <div class="md:col-span-3">
                <label for="search_month" class="mb-1 block text-xs font-bold uppercase text-slate-500">Search Month</label>
                <select name="search_month" id="search_month" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="0">---Select Month---</option>
                    @foreach($months as $month)
                        <option value="{{ $month }}" {{ $searchMonth === $month ? 'selected' : '' }}>{{ $month }}</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-2">
                <label for="search_year" class="mb-1 block text-xs font-bold uppercase text-slate-500">Year</label>
                <select name="search_year" id="search_year" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="0">---Year---</option>
                    @foreach($years as $year)
                        <option value="{{ $year }}" {{ $searchYear === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-3">
                <label for="expense_cat_filter" class="mb-1 block text-xs font-bold uppercase text-slate-500">Category</label>
                <select name="expense_cat_filter" id="expense_cat_filter" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="0">---Select category---</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ $expenseCatFilter === (string) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-4 flex flex-wrap items-center gap-2">
                <button type="submit" class="btn btn-success">Search</button>
                <a href="{{ route('admin.manage-expense.index') }}" class="btn btn-default">Reset</a>
                <button type="button" class="btn-brand !px-4 !py-2 text-sm" onclick="add_new_modal();">+ Add new</button>
                <button type="button" class="btn btn-success" title="Print All" onclick="print_data(); return false;"><i class="fa fa-print" aria-hidden="true"></i></button>
                <a class="btn btn-success" href="{{ route('admin.manage-expense.csv', request()->query()) }}" title="Export CSV">Export CSV</a>
            </div>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table id="example1" class="data-table min-w-[1300px]">
            <thead>
                <tr>
                    <th>SL No</th>
                    <th>Expense type</th>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Payment mode</th>
                    <th>Customer</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($expenses as $expense)
                    <tr>
                        <td><b>{{ $expenses->firstItem() + $loop->index }}</b></td>
                        <td><b>{{ $expense->category?->name ?? 'N/A' }}</b></td>
                        <td><b>{{ optional($expense->expense_date)->format('d/m/Y') ?? 'N/A' }}</b></td>
                        <td><b>Rs. {{ number_format((float) $expense->amount, 0) }}/-</b></td>
                        <td>
                            <b>
                                {{ $expense->payment_mode }}<br>
                                Cheque no. {{ $expense->cheque_no ?: 'N/A' }}<br>
                                Bank: {{ $expense->bank_name ?: 'N/A' }}
                            </b>
                        </td>
                        <td><b>{{ $expense->customer_name ?: 'N/A' }}</b></td>
                        <td>
                            <div class="flex flex-wrap items-center gap-2">
                                @if(!empty($expense->voucher_file))
                                    <a target="_blank" href="{{ asset('storage/' . $expense->voucher_file) }}" class="inline-flex items-center gap-1 rounded-lg bg-sky-100 px-3 py-2 text-xs font-semibold text-sky-700 hover:bg-sky-200" title="Download voucher">
                                        <i class="fa fa-download"></i>
                                        <span>Voucher</span>
                                    </a>
                                @endif
                                <a href="javascript:void(0);" class="inline-flex items-center gap-1 rounded-lg bg-emerald-100 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-200" onclick="edit_modal({{ $expense->id }});" title="Edit">
                                    <i class="ri-pencil-line"></i>
                                    <span>Edit</span>
                                </a>
                                <a href="javascript:void(0);" class="inline-flex items-center gap-1 rounded-lg bg-rose-100 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-200" onclick="delete_expense({{ $expense->id }});" title="Delete">
                                    <i class="ri-delete-bin-line"></i>
                                    <span>Delete</span>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-slate-500">No expense records found.</td>
                    </tr>
                @endforelse
            </tbody>
            <tbody>
                <tr style="font-weight: 700;">
                    <td></td>
                    <td></td>
                    <td style="text-align: right;">All total expense</td>
                    <td>Rs {{ number_format((float) $totalExpense, 0) }}/-</td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    </div>

    @if($expenses->hasPages())
        <div class="mt-4">{{ $expenses->links() }}</div>
    @endif

    <div id="print_content" style="display:none;">
        <h3 class="box-title">Manage expensive ({{ $expenses->total() }})</h3>
        <style type="text/css">
            #print_content td, #print_content th { border: solid 1px #777; margin: 2px; padding: 2px; }
        </style>

        <table class="table table-bordered table-striped" width="100%">
            <thead>
                <tr>
                    <th>SL No</th>
                    <th>Expense type</th>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Payment mode</th>
                    <th>Customer</th>
                </tr>
            </thead>
            <tbody>
                @foreach($expenses as $expense)
                    <tr>
                        <td><b>{{ $loop->iteration }}</b></td>
                        <td><b>{{ $expense->category?->name ?? 'N/A' }}</b></td>
                        <td><b>{{ optional($expense->expense_date)->format('d/m/Y') ?? 'N/A' }}</b></td>
                        <td><b>Rs. {{ number_format((float) $expense->amount, 0) }}/-</b></td>
                        <td><b>{{ $expense->payment_mode }}</b></td>
                        <td><b>{{ $expense->customer_name ?: 'N/A' }}</b></td>
                    </tr>
                @endforeach
                <tr style="font-weight:700;">
                    <td></td>
                    <td></td>
                    <td style="text-align:right;">All total expense</td>
                    <td>Rs {{ number_format((float) $totalExpense, 0) }}/-</td>
                    <td></td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<div id="expense_modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 px-4">
    <div class="modal-content w-full max-w-2xl rounded-xl bg-white shadow-2xl" onclick="event.stopPropagation();">
        <form action="{{ route('admin.manage-expense.legacy-store') }}" method="POST" accept-charset="utf-8" class="form-horizontal" id="add_new_form" enctype="multipart/form-data">
            @csrf
            <div class="modal-header flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <h3 id="expense_modal_title" class="text-lg font-semibold">Add new expensive</h3>
                <button type="button" class="close text-2xl leading-none" onclick="close_modal();">&times;</button>
            </div>

            <div class="modal-body max-h-[70vh] overflow-y-auto px-5 py-4">
                <fieldset>
                    <div class="control-group mb-4" id="edit_discount_control">
                        <label class="control-label mb-2 block text-sm font-semibold" for="expensive_cat">Expensive category</label>
                        <div class="controls">
                            <select id="expensive_cat" name="expense_category_id" class="form-control" required>
                                <option value="">--Select expensive category--</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="control-group mb-4" id="edit_discount_control">
                        <label class="control-label mb-2 block text-sm font-semibold" for="expense_date">Expense date</label>
                        <div class="controls">
                            <input type="date" id="expense_date" name="expense_date" class="form-control" required>
                        </div>
                    </div>

                    <div class="control-group mb-4" id="edit_discount_control">
                        <label class="control-label mb-2 block text-sm font-semibold" for="amount">Amount</label>
                        <div class="controls">
                            <input type="number" id="amount" step="0.01" min="0" name="amount" class="form-control" required>
                        </div>
                    </div>

                    <div class="control-group mb-4" id="edit_discount_control">
                        <label class="control-label mb-2 block text-sm font-semibold" for="note">Note</label>
                        <div class="controls">
                            <textarea id="note" name="remarks" class="form-control" rows="3"></textarea>
                        </div>
                    </div>

                    <div class="control-group mb-4" id="edit_discount_control">
                        <label class="control-label mb-2 block text-sm font-semibold" for="customer_name">Customer name</label>
                        <div class="controls">
                            <input type="text" id="customer_name" name="customer_name" class="form-control">
                        </div>
                    </div>

                    <div class="control-group mb-4" id="edit_discount_control">
                        <label class="control-label mb-2 block text-sm font-semibold" for="voucher">Upload voucher</label>
                        <div class="controls">
                            <input type="file" id="voucher" name="voucher_file" class="form-control">
                        </div>
                    </div>

                    <div class="control-group mb-4" id="edit_discount_control">
                        <label class="control-label mb-2 block text-sm font-semibold" for="payment_mode">Payment mode</label>
                        <div class="controls">
                            <select id="payment_mode" name="payment_mode" class="form-control" onchange="payment_mode_change();" required>
                                <option value="">--Select payment mode--</option>
                                <option value="cash">Cash</option>
                                <option value="cheque">Cheque</option>
                            </select>
                        </div>
                    </div>

                    <span id="for_cheque" style="display:none;">
                        <div class="control-group mb-4" id="edit_discount_control">
                            <label class="control-label mb-2 block text-sm font-semibold" for="cheque_no">Cheque no.</label>
                            <div class="controls">
                                <input type="text" id="cheque_no" name="cheque_no" class="form-control">
                            </div>
                        </div>

                        <div class="control-group" id="edit_discount_control">
                            <label class="control-label mb-2 block text-sm font-semibold" for="bank">Bank name</label>
                            <div class="controls">
                                <input type="text" id="bank" name="bank_name" class="form-control">
                            </div>
                        </div>
                    </span>
                </fieldset>
            </div>

            <div class="modal-footer flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4">
                <button type="button" class="btn btn-default" onclick="close_modal();">Close</button>
                <button type="submit" class="btn btn-primary" id="expense_submit_btn" onclick="return add_new_validation();">Add</button>
            </div>
        </form>
    </div>
</div>

<form id="delete_expense_form" method="POST" style="display:none;">@csrf</form>

<script>
function modalElement() {
    return document.getElementById('expense_modal');
}

function openModal() {
    modalElement().classList.remove('hidden');
    modalElement().classList.add('flex');
}

function close_modal() {
    modalElement().classList.add('hidden');
    modalElement().classList.remove('flex');
}

function print_data() {
    var divToPrint = document.getElementById('print_content');
    var newWin = window.open('');
    newWin.document.write(divToPrint.outerHTML);
    newWin.print();
    newWin.close();
}

function add_new_modal() {
    var form = document.getElementById('add_new_form');
    form.reset();
    form.action = @json(route('admin.manage-expense.legacy-store'));
    document.getElementById('expense_modal_title').textContent = 'Add new expensive';
    document.getElementById('expense_submit_btn').textContent = 'Add';
    payment_mode_change();
    openModal();
}

function edit_modal(id) {
    fetch(@json(route('admin.manage-expense.json', ['expense' => '__ID__'])).replace('__ID__', String(id)))
        .then(function (response) { return response.json(); })
        .then(function (data) {
            if (!data.success || !data.expense) {
                throw new Error('Unable to load expense details.');
            }

            var expense = data.expense;
            var form = document.getElementById('add_new_form');
            form.action = @json(route('admin.manage-expense.legacy-update', ['expense' => '__ID__'])).replace('__ID__', String(id));

            document.getElementById('expense_modal_title').textContent = 'Edit expensive';
            document.getElementById('expense_submit_btn').textContent = 'Update';
            document.getElementById('expensive_cat').value = String(expense.expense_category_id || '');
            document.getElementById('expense_date').value = expense.expense_date || '';
            document.getElementById('amount').value = expense.amount || '';
            document.getElementById('payment_mode').value = (expense.payment_mode || '');
            document.getElementById('cheque_no').value = expense.cheque_no || '';
            document.getElementById('bank').value = expense.bank_name || '';
            document.getElementById('customer_name').value = expense.customer_name || '';
            document.getElementById('note').value = expense.remarks || '';

            payment_mode_change();
            openModal();
        })
        .catch(function (error) {
            alert(error.message || 'Failed to load expense.');
        });
}

function delete_expense(id) {
    if (!confirm('Are you sure you want to delete this expense?')) {
        return;
    }

    var form = document.getElementById('delete_expense_form');
    form.action = @json(route('admin.manage-expense.legacy-destroy', ['expense' => '__ID__'])).replace('__ID__', String(id));
    form.submit();
}

function payment_mode_change() {
    var mode = String(document.getElementById('payment_mode').value || '').toLowerCase();
    var chequeFields = document.getElementById('for_cheque');
    if (mode === 'cheque') {
        chequeFields.style.display = 'inline';
    } else {
        chequeFields.style.display = 'none';
    }
}

function add_new_validation() {
    var category = document.getElementById('expensive_cat').value;
    var date = document.getElementById('expense_date').value;
    var amount = Number(document.getElementById('amount').value || 0);
    var paymentMode = document.getElementById('payment_mode').value;

    if (!category) {
        alert('Please select expense category.');
        return false;
    }
    if (!date) {
        alert('Please select date.');
        return false;
    }
    if (!amount) {
        alert('Please enter amount.');
        return false;
    }
    if (!paymentMode) {
        alert('Please select payment mode.');
        return false;
    }

    return true;
}

document.addEventListener('click', function (event) {
    if (event.target && event.target.id === 'expense_modal') {
        close_modal();
    }
});

document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
        close_modal();
    }
});
</script>
@endsection

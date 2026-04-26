@extends('admin.layouts.app')

@section('title', 'Manage Salary')
@section('page-title', 'Account Management')

@section('content')
<section class="section-card">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <h3 class="section-title mb-0">Employees salary data ({{ $salaries->total() }})</h3>
    </div>

    <div class="mb-4 rounded-xl border border-slate-200 bg-slate-50 p-3">
        <form method="GET" action="{{ route('admin.manage-salary.index') }}" class="grid grid-cols-1 gap-3 md:grid-cols-12 md:items-end">
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
                    <option value="0">---Select Year---</option>
                    @foreach($years as $year)
                        <option value="{{ $year }}" {{ $searchYear === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-3">
                <label for="search_employee" class="mb-1 block text-xs font-bold uppercase text-slate-500">Employee</label>
                <select name="search_employee" id="search_employee" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="0">---Select employee---</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" {{ $searchEmployee === (string) $employee->id ? 'selected' : '' }}>{{ $employee->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-4 flex flex-wrap items-center gap-2">
                <button type="submit" class="btn btn-success">Search</button>
                <a href="{{ route('admin.manage-salary.index') }}" class="btn btn-default">Reset</a>
                <button type="button" class="btn-brand !px-4 !py-2 text-sm" onclick="add_new_modal();">+ Generate salary</button>
                <button type="button" class="btn btn-success" title="Print All" onclick="print_data(); return false;"><i class="fa fa-print" aria-hidden="true"></i></button>
                <a class="btn btn-success" href="{{ route('admin.manage-salary.csv', request()->query()) }}" title="Export CSV">Export CSV</a>
            </div>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table id="example1" class="data-table min-w-[1300px]">
            <thead>
                <tr>
                    <th>SL No</th>
                    <th>Employee name</th>
                    <th>Salary</th>
                    <th>Month</th>
                    <th>Year</th>
                    <th>Payment Details</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($salaries as $salary)
                    <tr>
                        <td><b>{{ $salaries->firstItem() + $loop->index }}</b></td>
                        <td><b>{{ $salary->employee?->name ?? 'N/A' }}</b></td>
                        <td><b>Rs {{ number_format((float) $salary->net_salary, 0) }}/-</b></td>
                        <td><b>{{ $salary->salary_month }}</b></td>
                        <td><b>{{ $salary->salary_year }}</b></td>
                        <td>
                            <b>
                                Cheque No: {{ $salary->cheque_no ?: 'N/A' }}<br>
                                Bank Name: {{ $salary->bank_name ?: 'N/A' }}
                            </b>
                        </td>
                        <td>
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="javascript:void(0);" class="inline-flex items-center gap-1 rounded-lg bg-emerald-100 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-200" onclick="edit_modal({{ $salary->id }});" title="Edit">
                                    <i class="ri-pencil-line"></i>
                                    <span>Edit</span>
                                </a>
                                <a target="_blank" href="{{ route('admin.manage-salary.slip', $salary->id) }}" class="inline-flex items-center gap-1 rounded-lg bg-sky-100 px-3 py-2 text-xs font-semibold text-sky-700 hover:bg-sky-200" title="Print slip">
                                    <i class="fa fa-print"></i>
                                    <span>Slip</span>
                                </a>
                                <a target="_blank" href="{{ route('admin.manage-salary.show', $salary->id) }}" class="inline-flex items-center gap-1 rounded-lg bg-indigo-100 px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-200" title="View">
                                    <i class="fa fa-eye"></i>
                                    <span>View</span>
                                </a>
                                <a href="javascript:void(0);" class="inline-flex items-center gap-1 rounded-lg bg-rose-100 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-200" onclick="delete_salary({{ $salary->id }});" title="Delete">
                                    <i class="ri-delete-bin-line"></i>
                                    <span>Delete</span>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-slate-500">No salary records found.</td>
                    </tr>
                @endforelse
            </tbody>
            <tbody>
                <tr style="font-weight:700;">
                    <td></td>
                    <td style="text-align:right;">All total salary</td>
                    <td>Rs {{ number_format((float) $totalSalary, 0) }}/-</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    </div>

    @if($salaries->hasPages())
        <div class="mt-4">{{ $salaries->links() }}</div>
    @endif

    <div id="print_content" style="display:none;">
        <h3 class="box-title">Employees salary data ({{ $salaries->total() }})</h3>
        <style type="text/css">
            #print_content td, #print_content th { border: solid 1px #777; margin: 2px; padding: 2px; }
        </style>

        <table class="table table-bordered table-striped" width="100%">
            <thead>
                <tr>
                    <th>SL No</th>
                    <th>Employee</th>
                    <th>Salary</th>
                    <th>Month</th>
                    <th>Year</th>
                </tr>
            </thead>
            <tbody>
                @foreach($salaries as $salary)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $salary->employee?->name ?? 'N/A' }}</td>
                        <td>Rs {{ number_format((float) $salary->net_salary, 0) }}/-</td>
                        <td>{{ $salary->salary_month }}</td>
                        <td>{{ $salary->salary_year }}</td>
                    </tr>
                @endforeach
                <tr style="font-weight:700;">
                    <td></td>
                    <td style="text-align:right;">All total salary</td>
                    <td>Rs {{ number_format((float) $totalSalary, 0) }}/-</td>
                    <td></td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<div id="salary_modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 px-4">
    <div class="modal-content w-full max-w-2xl rounded-xl bg-white shadow-2xl" onclick="event.stopPropagation();">
        <form action="{{ route('admin.manage-salary.legacy-store') }}" method="POST" accept-charset="utf-8" class="form-horizontal" id="add_new_form">
            @csrf
            <div class="modal-header flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <h3 id="salary_modal_title" class="text-lg font-semibold">Generate Salary</h3>
                <button type="button" class="close text-2xl leading-none" onclick="close_modal();">&times;</button>
            </div>

            <div class="modal-body max-h-[70vh] overflow-y-auto px-5 py-4">
                <fieldset>
                    <div class="control-group mb-4" id="edit_discount_control">
                        <label class="control-label mb-2 block text-sm font-semibold">Choose year</label>
                        <div class="controls">
                            <select id="year" name="year" class="form-control" required>
                                <option value="0">--Choose year--</option>
                                @foreach($years as $year)
                                    <option value="{{ $year }}">{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="control-group mb-4" id="edit_discount_control">
                        <label class="control-label mb-2 block text-sm font-semibold">Choose month</label>
                        <div class="controls">
                            <select id="month" name="month" class="form-control" required>
                                <option value="0">--Select month--</option>
                                @foreach($months as $month)
                                    <option value="{{ $month }}">{{ $month }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="control-group mb-4" id="edit_discount_control">
                        <label class="control-label mb-2 block text-sm font-semibold">Choose employee</label>
                        <div class="controls">
                            <select class="form-control" id="employee" name="employee" onchange="get_employee_content(this.value);" required>
                                <option value="0">--Select employee--</option>
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}" data-salary="{{ (float) $employee->salary }}">{{ $employee->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div id="after_emp_data_load" style="display:none;">
                        <div class="control-group mb-4" id="edit_discount_control">
                            <label class="control-label mb-2 block text-sm font-semibold">Monthly salary</label>
                            <div class="controls">
                                <input style="cursor:not-allowed;" type="number" step="0.01" class="form-control" id="monthly_salary" name="monthly_salary" readonly>
                            </div>
                        </div>

                        <div class="control-group mb-4" id="edit_discount_control">
                            <label class="control-label mb-2 block text-sm font-semibold">Total login day</label>
                            <div class="controls">
                                <input type="number" min="0" max="31" id="total_login_day" name="total_login_day" class="form-control">
                            </div>
                        </div>

                        <div class="control-group mb-4" id="edit_discount_control">
                            <label class="control-label mb-2 block text-sm font-semibold">Total absense</label>
                            <div class="controls">
                                <input type="number" min="0" max="31" id="total_absense" name="total_absense" class="form-control" value="0">
                            </div>
                        </div>

                        <div class="control-group mb-4" id="edit_discount_control">
                            <label class="control-label mb-2 block text-sm font-semibold">Absense reason</label>
                            <div class="controls">
                                <textarea id="absense_reason" name="absense_reason" class="form-control" rows="2"></textarea>
                            </div>
                        </div>

                        <div class="control-group mb-4" id="edit_discount_control">
                            <label class="control-label mb-2 block text-sm font-semibold">Incentive</label>
                            <div class="controls">
                                <input type="number" step="0.01" min="0" id="intensive" name="incentive" class="form-control" value="0">
                            </div>
                        </div>

                        <div class="control-group mb-4" id="edit_discount_control">
                            <label class="control-label mb-2 block text-sm font-semibold">Incentive for</label>
                            <div class="controls">
                                <textarea id="intensive_for" name="incentive_for" class="form-control" rows="2"></textarea>
                            </div>
                        </div>

                        <div class="control-group mb-4" id="edit_discount_control">
                            <label class="control-label mb-2 block text-sm font-semibold">Advance</label>
                            <div class="controls">
                                <input type="number" step="0.01" min="0" id="advance" name="advance" class="form-control" value="0">
                            </div>
                        </div>

                        <div class="control-group mb-4" id="edit_discount_control">
                            <label class="control-label mb-2 block text-sm font-semibold">Additional deduct</label>
                            <div class="controls">
                                <input type="number" step="0.01" min="0" id="additional_deduct" name="additional_deduct" class="form-control" value="0">
                            </div>
                        </div>

                        <div class="control-group mb-4" id="edit_discount_control">
                            <label class="control-label mb-2 block text-sm font-semibold">Additional deduction reason</label>
                            <div class="controls">
                                <input type="text" id="additional_deduct_reason" name="additional_deduct_reason" class="form-control">
                            </div>
                        </div>

                        <div class="control-group mb-4" id="edit_discount_control">
                            <label class="control-label mb-2 block text-sm font-semibold">Office duty</label>
                            <div class="controls">
                                <input type="number" step="0.01" min="0" id="od" name="office_duty" class="form-control" value="0">
                            </div>
                        </div>

                        <div class="control-group mb-4" id="edit_discount_control">
                            <label class="control-label mb-2 block text-sm font-semibold">Bonus</label>
                            <div class="controls">
                                <input type="number" step="0.01" min="0" id="bonus" name="bonus" class="form-control" value="0">
                            </div>
                        </div>

                        <div class="control-group mb-4" id="edit_discount_control">
                            <label class="control-label mb-2 block text-sm font-semibold">PF (Provident fund)</label>
                            <div class="controls">
                                <input type="number" step="0.01" min="0" id="pf" name="pf" class="form-control" value="0">
                            </div>
                        </div>

                        <div class="control-group mb-4" id="edit_discount_control">
                            <label class="control-label mb-2 block text-sm font-semibold">E.S.I</label>
                            <div class="controls">
                                <input type="number" step="0.01" min="0" id="esi" name="esi" class="form-control" value="0">
                            </div>
                        </div>

                        <div class="control-group mb-4" id="edit_discount_control">
                            <label class="control-label mb-2 block text-sm font-semibold">Professional Tax</label>
                            <div class="controls">
                                <input type="number" step="0.01" min="0" id="ptax" name="ptax" class="form-control" value="0">
                            </div>
                        </div>

                        <div class="control-group mb-4" id="edit_discount_control">
                            <label class="control-label mb-2 block text-sm font-semibold">Cheque No.</label>
                            <div class="controls">
                                <input type="text" id="checque_no" name="cheque_no" class="form-control">
                            </div>
                        </div>

                        <div class="control-group" id="edit_discount_control">
                            <label class="control-label mb-2 block text-sm font-semibold">Bank Name</label>
                            <div class="controls">
                                <input type="text" id="bank_name" name="bank_name" class="form-control">
                            </div>
                        </div>
                    </div>
                </fieldset>
            </div>

            <div class="modal-footer flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4">
                <button type="button" class="btn btn-default" onclick="close_modal();">Close</button>
                <button type="submit" class="btn btn-primary" id="salary_submit_btn" onclick="return add_new_validation();">Submit</button>
            </div>
        </form>
    </div>
</div>

<form id="delete_salary_form" method="POST" style="display:none;">@csrf</form>

<script>
function modalElement() {
    return document.getElementById('salary_modal');
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

function get_employee_content(employeeId) {
    var container = document.getElementById('after_emp_data_load');
    var select = document.getElementById('employee');
    var option = select.options[select.selectedIndex];

    if (!employeeId || employeeId === '0') {
        container.style.display = 'none';
        document.getElementById('monthly_salary').value = '';
        return;
    }

    document.getElementById('monthly_salary').value = option ? (option.getAttribute('data-salary') || '0') : '0';
    container.style.display = 'block';
}

function add_new_modal() {
    var form = document.getElementById('add_new_form');
    form.reset();
    form.action = @json(route('admin.manage-salary.legacy-store'));
    document.getElementById('salary_modal_title').textContent = 'Generate Salary';
    document.getElementById('salary_submit_btn').textContent = 'Submit';
    document.getElementById('after_emp_data_load').style.display = 'none';
    openModal();
}

function edit_modal(id) {
    fetch(@json(route('admin.manage-salary.json', ['salaryRecord' => '__ID__'])).replace('__ID__', String(id)))
        .then(function (response) { return response.json(); })
        .then(function (data) {
            if (!data.success || !data.salary) {
                throw new Error('Unable to load salary details.');
            }

            var row = data.salary;
            var form = document.getElementById('add_new_form');
            form.action = @json(route('admin.manage-salary.legacy-update', ['salaryRecord' => '__ID__'])).replace('__ID__', String(id));

            document.getElementById('salary_modal_title').textContent = 'Edit Salary';
            document.getElementById('salary_submit_btn').textContent = 'Update';

            document.getElementById('year').value = String(row.salary_year || '0');
            document.getElementById('month').value = row.salary_month || '0';
            document.getElementById('employee').value = String(row.user_id || '0');
            document.getElementById('monthly_salary').value = row.monthly_salary || 0;
            document.getElementById('total_login_day').value = row.total_login_day || '';
            document.getElementById('total_absense').value = row.total_absense || 0;
            document.getElementById('absense_reason').value = row.absense_reason || '';
            document.getElementById('intensive').value = row.incentive || 0;
            document.getElementById('intensive_for').value = row.incentive_for || '';
            document.getElementById('advance').value = row.advance || 0;
            document.getElementById('additional_deduct').value = row.additional_deduct || 0;
            document.getElementById('additional_deduct_reason').value = row.additional_deduct_reason || '';
            document.getElementById('od').value = row.office_duty || 0;
            document.getElementById('bonus').value = row.bonus || 0;
            document.getElementById('pf').value = row.pf || 0;
            document.getElementById('esi').value = row.esi || 0;
            document.getElementById('ptax').value = row.ptax || 0;
            document.getElementById('checque_no').value = row.cheque_no || '';
            document.getElementById('bank_name').value = row.bank_name || '';

            document.getElementById('after_emp_data_load').style.display = 'block';
            openModal();
        })
        .catch(function (error) {
            alert(error.message || 'Failed to load salary.');
        });
}

function add_new_validation() {
    var year = document.getElementById('year').value;
    var month = document.getElementById('month').value;
    var employee = document.getElementById('employee').value;

    if (!year || year === '0') {
        alert('Please select year.');
        return false;
    }

    if (!month || month === '0') {
        alert('Please select month.');
        return false;
    }

    if (!employee || employee === '0') {
        alert('Please select employee.');
        return false;
    }

    return true;
}

function delete_salary(id) {
    if (!confirm('Are you sure you want to delete this salary record?')) {
        return;
    }

    var form = document.getElementById('delete_salary_form');
    form.action = @json(route('admin.manage-salary.legacy-destroy', ['salaryRecord' => '__ID__'])).replace('__ID__', String(id));
    form.submit();
}

document.addEventListener('click', function (event) {
    if (event.target && event.target.id === 'salary_modal') {
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

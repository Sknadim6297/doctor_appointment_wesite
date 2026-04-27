@extends('admin.layouts.app')

@section('title', 'Office Expensions')
@section('page-title', 'Account Management')

@section('content')
<section class="section-card">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <h3 class="section-title mb-0">Expenses Summary</h3>
    </div>

    <div class="mb-4 rounded-xl border border-slate-200 bg-slate-50 p-3">
        <form method="GET" action="{{ route('admin.office-expensions.index') }}" class="grid grid-cols-1 gap-3 md:grid-cols-12 md:items-end">
            <div class="md:col-span-3">
                <label for="search_month" class="mb-1 block text-xs font-bold uppercase text-slate-500">Search Month</label>
                <select name="search_month" id="search_month" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="0">---Select Month---</option>
                    @foreach($months as $month)
                        <option value="{{ $month }}" {{ $selectedMonth === $month ? 'selected' : '' }}>{{ $month }}</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-3">
                <label for="search_year" class="mb-1 block text-xs font-bold uppercase text-slate-500">Search Year</label>
                <select name="search_year" id="search_year" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="0">---Select Year---</option>
                    @foreach($years as $year)
                        <option value="{{ $year }}" {{ $selectedYear === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-6 flex flex-wrap items-center gap-2">
                <button type="submit" class="btn btn-success">Search</button>
                <a href="{{ route('admin.office-expensions.index') }}" class="btn btn-default">Reset</a>
                <button type="button" class="btn btn-success" title="Print All" onclick="print_data(); return false;"><i class="fa fa-print" aria-hidden="true"></i></button>
                <a class="btn btn-success" href="{{ route('admin.office-expensions.csv', request()->query()) }}" title="Export CSV">Export CSV</a>
            </div>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table id="example1" class="data-table min-w-[900px]">
            <thead>
                <tr>
                    <th>Total office expense</th>
                    <th>Total salary expense</th>
                    <th>Total expense</th>
                    <th>Total income</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><b>Rs. {{ number_format((float) $totalOfficeExpense, 0) }}/-</b></td>
                    <td><b>Rs. {{ number_format((float) $totalSalaryExpense, 0) }}/-</b></td>
                    <td><b>Rs. {{ number_format((float) $totalExpense, 0) }}/-</b></td>
                    <td><b>Rs. {{ number_format((float) $totalIncome, 0) }}/-</b></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div id="print_content" style="display:none;">
        <h3 class="box-title">Expenses summary</h3>
        <style type="text/css">
            #print_content td, #print_content th { border: solid 1px #777; margin: 2px; padding: 2px; }
        </style>

        <table class="table table-bordered table-striped" width="100%">
            <thead>
                <tr>
                    <th>Total office expense</th>
                    <th>Total salary expense</th>
                    <th>Total expense</th>
                    <th>Total income</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Rs. {{ number_format((float) $totalOfficeExpense, 0) }}/-</td>
                    <td>Rs. {{ number_format((float) $totalSalaryExpense, 0) }}/-</td>
                    <td>Rs. {{ number_format((float) $totalExpense, 0) }}/-</td>
                    <td>Rs. {{ number_format((float) $totalIncome, 0) }}/-</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<script>
function print_data() {
    var divToPrint = document.getElementById('print_content');
    var newWin = window.open('');
    newWin.document.write(divToPrint.outerHTML);
    newWin.print();
    newWin.close();
}
</script>
@endsection

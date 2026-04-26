@extends('admin.layouts.app')

@section('title', 'Salary Details')
@section('page-title', 'Account Management')

@section('content')
<section class="section-card">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <h3 class="section-title mb-0">Salary details</h3>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.manage-salary.index') }}" class="btn btn-default">Back</a>
            <a href="{{ route('admin.manage-salary.slip', $salary->id) }}" target="_blank" class="btn btn-primary">Print slip</a>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <h4 class="mb-3 text-sm font-bold uppercase text-slate-500">Employee</h4>
            <p><b>Name:</b> {{ $salary->employee?->name ?? 'N/A' }}</p>
            <p><b>Employee no:</b> {{ $salary->employee?->employee_no ?? 'N/A' }}</p>
            <p><b>Month:</b> {{ $salary->salary_month }}</p>
            <p><b>Year:</b> {{ $salary->salary_year }}</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <h4 class="mb-3 text-sm font-bold uppercase text-slate-500">Payment</h4>
            <p><b>Monthly salary:</b> Rs {{ number_format((float) $salary->monthly_salary, 2) }}</p>
            <p><b>Net salary:</b> Rs {{ number_format((float) $salary->net_salary, 2) }}</p>
            <p><b>Cheque no:</b> {{ $salary->cheque_no ?: 'N/A' }}</p>
            <p><b>Bank name:</b> {{ $salary->bank_name ?: 'N/A' }}</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4 md:col-span-2">
            <h4 class="mb-3 text-sm font-bold uppercase text-slate-500">Breakdown</h4>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <p><b>Total login day:</b> {{ $salary->total_login_day ?? 'N/A' }}</p>
                <p><b>Total absense:</b> {{ $salary->total_absense }}</p>
                <p><b>Incentive:</b> Rs {{ number_format((float) $salary->incentive, 2) }}</p>
                <p><b>Advance:</b> Rs {{ number_format((float) $salary->advance, 2) }}</p>
                <p><b>Additional deduct:</b> Rs {{ number_format((float) $salary->additional_deduct, 2) }}</p>
                <p><b>Office duty:</b> Rs {{ number_format((float) $salary->office_duty, 2) }}</p>
                <p><b>Bonus:</b> Rs {{ number_format((float) $salary->bonus, 2) }}</p>
                <p><b>PF:</b> Rs {{ number_format((float) $salary->pf, 2) }}</p>
                <p><b>ESI:</b> Rs {{ number_format((float) $salary->esi, 2) }}</p>
                <p><b>P-Tax:</b> Rs {{ number_format((float) $salary->ptax, 2) }}</p>
            </div>
            <div class="mt-4">
                <p><b>Absense reason:</b> {{ $salary->absense_reason ?: 'N/A' }}</p>
                <p><b>Incentive for:</b> {{ $salary->incentive_for ?: 'N/A' }}</p>
                <p><b>Additional deduction reason:</b> {{ $salary->additional_deduct_reason ?: 'N/A' }}</p>
            </div>
        </div>
    </div>
</section>
@endsection

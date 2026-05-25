@extends('admin.layouts.app')

@section('title', 'Legacy Money Receipt')
@section('page-title', 'Legacy Money Receipt')

@section('content')
<section class="section-card">
    <div class="mb-5">
        <h3 class="section-title">Legacy money receipt — {{ $enrollment->doctor_name ?? 'Doctor #'.$enrollment->doctor_id }}</h3>
        <p class="text-muted">Enrollment #{{ $enrollment->id }} · Policy {{ $policyReceipt->policy_no ?? '—' }}</p>
    </div>

    <form action="{{ route('admin.policy-receipt.legacy-update', $policyReceipt->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <div class="checkbox">
                <label>
                    <input type="checkbox" name="doctor_only" value="1" {{ old('doctor_only', 1) ? 'checked' : '' }}>
                    Doctor only (update enrollment money receipt fields only)
                </label>
            </div>
        </div>

        <div class="mb-3">
            <label class="block text-sm font-medium">Money Receipt No</label>
            <input type="number" name="money_reciept_no" class="form-control" min="1" value="{{ old('money_reciept_no', $policyReceipt->enrollment?->doctor_money_reciept_no) }}">
        </div>
        <div class="mb-3">
            <label class="block text-sm font-medium">Money Receipt Year</label>
            <input type="number" name="money_reciept_year" class="form-control" min="1900" max="2100" value="{{ old('money_reciept_year', $policyReceipt->enrollment?->money_reciept_year) }}">
        </div>

        <div class="flex gap-2">
            <button type="submit" class="btn btn-primary">Save</button>
            <a href="{{ route('admin.policy-receipt.legacy-edit', $policyReceipt->id) }}" class="btn btn-default">Cancel</a>
            <a href="{{ route('admin.enrollment.show', $enrollment->id) }}" class="btn btn-default">Back to enrollment</a>
        </div>
    </form>
</section>
@endsection
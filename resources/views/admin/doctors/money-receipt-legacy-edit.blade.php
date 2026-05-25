@extends('admin.layouts.app')

@section('title', 'Money Receipt (Doctor Only)')
@section('page-title', 'Money Receipt')

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title mb-0">Money Receipt — {{ $enrollment->doctor_name ?? 'Doctor' }}</h3>
        <p class="text-muted mb-0">
            Enrollment #{{ $enrollment->id }} · Legacy user {{ $enrollment->legacy_user_id ?? '—' }}
            @if($enrollment->customer_id_no)
                · Customer {{ $enrollment->customer_id_no }}
            @endif
        </p>
    </div>
    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('admin.doctors.money-receipt.legacy-update', $enrollment->id) }}">
            @csrf
            <input type="hidden" name="doctor_only" value="1">

            <div class="row">
                <div class="col-md-4">
                    <label class="form-label">Money Receipt No</label>
                    <input type="text" name="doctor_money_reciept_no" class="form-control" value="{{ old('doctor_money_reciept_no', $enrollment->doctor_money_reciept_no) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Money Receipt Year</label>
                    <input type="text" name="doctor_money_reciept_year" class="form-control" value="{{ old('doctor_money_reciept_year', $enrollment->doctor_money_reciept_year) }}" placeholder="e.g. 2019">
                </div>
            </div>

            <div class="mt-3">
                <label class="form-label">Payment Amount (read-only)</label>
                <input type="text" class="form-control" value="{{ $enrollment->payment_amount }}" readonly>
            </div>

            <div class="mt-3">
                <a href="{{ route('admin.enrollment.show', $enrollment->id) }}" class="btn btn-outline-secondary btn-sm">Back to Enrollment</a>
                <a href="{{ route('admin.doctors.show', $enrollment->legacy_user_id) }}" class="btn btn-outline-primary btn-sm">Doctor Profile</a>
            </div>
        </form>
    </div>
</div>
@endsection
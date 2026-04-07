@extends('admin.layouts.app')

@section('title', 'View Policy Received')
@section('page-title', 'View Policy Received')

@section('content')
<section class="section-card">
    <div class="mb-5">
        <h3 class="section-title">View Policy Received #{{ $policy->id }}</h3>
    </div>

    <div class="mb-4 grid grid-cols-1 gap-3">
        <div><strong>Policy No:</strong> {{ $policy->policy_no ?? '—' }}</div>
        <div><strong>Doctor:</strong> {{ $policy->doctor_name ?? ($policy->enrollment?->doctor_name ?? '—') }}</div>
        <div><strong>Last Renewed Date:</strong> {{ optional($policy->last_renewed_date)->format('d/m/Y') ?? '—' }}</div>
        <div><strong>Receive Date:</strong> {{ optional($policy->receive_date)->format('d/m/Y') ?? '—' }}</div>
        <div>
            <strong>File:</strong>
            @if($policy->policy_file)
                <a href="{{ asset('storage/' . $policy->policy_file) }}" target="_blank" class="text-blue-600 underline">Download</a>
            @else
                —
            @endif
        </div>
    </div>

    <a href="{{ route('admin.policy-receipt.index') }}" class="btn btn-default">Back</a>
</section>
@endsection

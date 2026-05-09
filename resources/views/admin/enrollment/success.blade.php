@extends('admin.layouts.app')

@section('title', 'Enrollment Entry Success')
@section('page-title', 'Enrollment Entry Success')

@section('content')
<section class="mx-auto max-w-3xl">
    <div class="rounded-2xl border border-slate-200 bg-white px-6 py-8 shadow-sm text-center">
        <h1 class="text-2xl font-bold text-green-700">Enrollment Entry Success</h1>
        <p class="mt-3 text-slate-600">Doctor <strong>{{ $enrollment->doctor_name }}</strong> has been successfully enrolled and all steps are complete.</p>
        <div class="mt-6 text-left">
            <h3 class="font-semibold">Policy Received Records</h3>
            @if(!empty($policyReceipts) && $policyReceipts->count())
                <ul class="mt-2 list-disc pl-5 text-sm">
                    @foreach($policyReceipts as $pr)
                        <li>{{ $pr->policy_no ?? '—' }} — Received: {{ optional($pr->receive_date)->format('d/m/Y') ?? '—' }} @if($pr->policy_file) — <a href="{{ asset('storage/' . $pr->policy_file) }}" target="_blank">File</a>@endif</li>
                    @endforeach
                </ul>
            @else
                <div class="text-sm text-slate-500 mt-2">No policy received records found.</div>
            @endif
        </div>

        <div class="mt-6">
            <a href="{{ route('admin.enrollment') }}" class="rounded-xl bg-blue-600 px-4 py-2 text-white">Back to Enrollment List</a>
            <a href="{{ route('admin.enrollment.edit', $enrollment->id) }}" class="ml-3 rounded-xl border px-4 py-2">View / Edit Enrollment</a>
        </div>
    </div>
</section>
@endsection

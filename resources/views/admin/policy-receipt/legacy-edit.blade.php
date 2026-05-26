@extends('admin.layouts.app')

@section('title', 'Doctor Money Receipt')
@section('page-title', 'Doctor Money Receipt')

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.doctors.show', $enrollment->id) }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 hover:text-slate-900 mb-4">
        <i class="ri-arrow-left-line"></i>
        Back to Doctor Profile
    </a>
    <span class="text-sm text-slate-500 mb-4">Enrollment #{{ $enrollment->id }}</span>
</div>

<div class="max-w-xl mx-auto">
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900 mb-1">Doctor money receipt</h2>
        <p class="text-sm text-slate-500 mb-6">These fields are stored on the enrollment record and are separate from the policy receipt form on the enrollment workflow.</p>

        <form method="POST" action="{{ route('admin.enrollment.doctor-money-receipt.update', $enrollment->id) }}">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="doctor_money_reciept_no" class="block text-sm font-semibold text-slate-700 mb-2">Money Receipt No</label>
                    <input type="text" name="doctor_money_reciept_no" id="doctor_money_reciept_no" value="{{ old('doctor_money_reciept_no', $enrollment->doctor_money_reciept_no) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    @error('doctor_money_reciept_no')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="doctor_money_reciept_year" class="block text-sm font-semibold text-slate-700 mb-2">Money Receipt Year</label>
                    <input type="text" name="doctor_money_reciept_year" id="doctor_money_reciept_year" value="{{ old('doctor_money_reciept_year', $enrollment->doctor_money_reciept_year) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    @error('doctor_money_reciept_year')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Save</button>
                <a href="{{ route('admin.doctors.show', $enrollment->id) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
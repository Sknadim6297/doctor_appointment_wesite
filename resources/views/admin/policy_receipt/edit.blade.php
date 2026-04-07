@extends('admin.layouts.app')

@section('title', 'Edit Policy Received')
@section('page-title', 'Edit Policy Received')

@section('content')
<section class="section-card">
    <div class="mb-5">
        <h3 class="section-title">Edit Policy Received #{{ $policy->id }}</h3>
    </div>

    <form action="{{ route('admin.policy-receipt.update', $policy->id) }}" method="POST" accept-charset="utf-8" class="form-horizontal" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label class="block text-sm font-medium">Doctor</label>
            <select id="doctor" name="doctor" class="form-control select2">
                <option value="">-- Select doctor --</option>
                @foreach($doctors as $d)
                    <option value="{{ $d->id }}" {{ $policy->enrollment_id == $d->id ? 'selected' : '' }}>{{ $d->doctor_name }}{{ $d->money_rc_no ? ' (' . $d->money_rc_no . ')' : '' }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="block text-sm font-medium">Policy No</label>
            <input name="policy_no" value="{{ old('policy_no', $policy->policy_no) }}" class="form-control" />
        </div>

        <div class="mb-3">
            <label class="block text-sm font-medium">Last Renewed Date (dd/mm/YYYY)</label>
            <input id="last_renewed_date" name="last_renewed_date" value="{{ old('last_renewed_date', optional($policy->last_renewed_date)->format('d/m/Y')) }}" class="form-control datepicker" />
        </div>

        <div class="mb-3">
            <label class="block text-sm font-medium">Receive Date (dd/mm/YYYY)</label>
            <input id="rcv_date" name="rcv_date" value="{{ old('rcv_date', optional($policy->receive_date)->format('d/m/Y')) }}" class="form-control datepicker" />
        </div>

        <div class="mb-3">
            <label class="block text-sm font-medium">Replace File (optional)</label>
            <input type="file" name="policy_file" class="form-control" />
            @if($policy->policy_file)
                <div class="mt-2"><a href="{{ asset('storage/' . $policy->policy_file) }}" target="_blank">Current file</a></div>
            @endif
        </div>

        <div class="flex gap-2">
            <button class="btn btn-primary" type="submit">Update</button>
            <a href="{{ route('admin.policy-receipt.index') }}" class="btn btn-default">Cancel</a>
        </div>
    </form>
</section>

@push('scripts')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        // Initialize Flatpickr
        if (typeof flatpickr !== 'undefined') {
            document.querySelectorAll('.datepicker').forEach(input => {
                flatpickr(input, { dateFormat: 'd/m/Y' });
            });
        }
        // Initialize Select2
        if (typeof $ !== 'undefined' && $.fn.select2) {
            $('.select2').select2({ width: '100%' });
        }
    </script>
@endpush
@endsection

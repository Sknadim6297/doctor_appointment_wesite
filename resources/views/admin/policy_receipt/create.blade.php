@extends('admin.layouts.app')

@section('title', 'Add Policy Received')
@section('page-title', 'Add Policy Received')

@section('content')
<section class="section-card">
    <div class="mb-5">
        <h3 class="section-title">Add Policy Received</h3>
    </div>

    <form action="{{ route('admin.policy-receipt.store') }}" method="POST" accept-charset="utf-8" class="form-horizontal" id="add_received_form" enctype="multipart/form-data">
        @csrf
        <div class="modal-header">
            <h3>Submit new policy received</h3>
        </div>
        <div class="modal-body">
            <fieldset>
                <div class="control-group" id="edit_discount_control">
                    <label class="control-label">Doctor</label>
                    <div class="controls">
                        <select style="width:100%" id="doctor" class="form-control select2" name="doctor" onchange="retrive_renewed_date(this.value);">
                            <option value="0" selected>--Select doctor--</option>
                            @foreach($doctors as $d)
                                <option value="{{ $d->id }}">{{ $d->doctor_name }}{{ $d->money_rc_no ? ' (' . $d->money_rc_no . ')' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="control-group" id="edit_discount_control">
                    <label class="control-label">Policy no.</label>
                    <div class="controls">
                        <input type="text" id="policy_no" class="form-control" name="policy_no" autocomplete="off">
                        <br>
                    </div>
                </div>

                <div class="control-group" id="edit_discount_control">
                    <label class="control-label">Last renewed date</label>
                    <div class="controls">
                        <input type="hidden" name="renew_history_id" id="renew_history_id">
                        <input type="text" id="last_renewed_date" class="form-control datepicker" name="last_renewed_date" autocomplete="off">
                    </div>
                </div>

                <div class="control-group" id="edit_discount_control">
                    <label class="control-label">Received date</label>
                    <div class="controls">
                        <input type="text" id="rcv_date" class="form-control datepicker" name="rcv_date" autocomplete="off">
                    </div>
                </div>

                <div class="control-group" id="edit_discount_control">
                    <label class="control-label">Policy received</label>
                    <div class="controls">
                        <input type="file" name="policy_file" id="policy_file">
                        <br>
                    </div>
                </div>
            </fieldset>
        </div>

        <div class="modal-footer">
            <a href="{{ route('admin.policy-receipt.index') }}" class="btn btn-default">Close</a>
            <button type="submit" class="btn btn-primary">Submit</button>
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

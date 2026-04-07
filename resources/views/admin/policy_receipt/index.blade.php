@extends('admin.layouts.app')

@section('title', 'Policy Received')
@section('page-title', 'Policy Received')

@section('content')
<section class="section-card">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <h3 class="section-title mb-0">Policy Received ({{ $policies->total() }})</h3>
        <div class="flex flex-wrap items-center gap-2">
            <form method="GET" action="{{ route('admin.policy-receipt.index') }}" class="flex items-center gap-2">
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search by policy or doctor"
                    class="master-search-input"
                >
                <button type="submit" class="btn btn-primary">Search</button>
                @if(request()->filled('search'))
                    <a href="{{ route('admin.policy-receipt.index') }}" class="btn btn-default">Clear</a>
                @endif
            </form>

            <button type="button" class="btn-brand !px-4 !py-2 text-sm" onclick="add_received_modal();">
                <i class="ri-add-line"></i>
                <span>Add new</span>
            </button>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>SL No</th>
                    <th>POLICY NO.</th>
                    <th>DOCTOR</th>
                    <th>LAST RENEWED DATE</th>
                    <th>RECIEVE DATE</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($policies as $policy)
                    <tr>
                        <td>{{ $policies->firstItem() + $loop->index }}</td>
                        <td>{{ $policy->policy_no ?? '—' }}</td>
                        <td>{{ $policy->doctor_name ?? ($policy->enrollment?->doctor_name ?? '—') }}</td>
                        <td>{{ optional($policy->last_renewed_date)->format('d/m/Y') ?? '—' }}</td>
                        <td>{{ optional($policy->receive_date)->format('d/m/Y') ?? '—' }}</td>
                        <td>
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('admin.policy-receipt.show', $policy->id) }}" class="inline-flex items-center gap-1 rounded-lg bg-sky-100 px-3 py-2 text-xs font-semibold text-sky-700 hover:bg-sky-200">
                                    <i class="ri-eye-line"></i>
                                    <span>View</span>
                                </a>
                                <a href="{{ route('admin.policy-receipt.edit', $policy->id) }}" class="inline-flex items-center gap-1 rounded-lg bg-emerald-100 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-200">
                                    <i class="ri-pencil-line"></i>
                                    <span>Edit</span>
                                </a>
                                <form method="POST" action="{{ route('admin.policy-receipt.destroy', $policy->id) }}" onsubmit="return confirm('Delete this entry?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center gap-1 rounded-lg bg-rose-100 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-200">
                                        <i class="ri-delete-bin-line"></i>
                                        <span>Delete</span>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-slate-500">No policy receipts found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($policies->hasPages())
        <div class="mt-4">{{ $policies->links() }}</div>
    @endif
</section>

<div id="receivedModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 px-4">
    <div class="modal-content w-full max-w-2xl rounded-xl bg-white shadow-2xl">
        <form action="{{ route('admin.policy-receipt.store') }}" method="post" accept-charset="utf-8" class="form-horizontal" id="add_received_form" enctype="multipart/form-data">
            @csrf
            <div class="modal-header flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <h3 class="text-lg font-semibold">Submit new policy received</h3>
                <button type="button" class="close text-2xl leading-none" onclick="close_received_modal();">&times;</button>
            </div>

            <div class="modal-body max-h-[70vh] overflow-y-auto px-5 py-4">
                <fieldset>
                    <div class="control-group mb-4" id="edit_discount_control">
                        <label class="control-label mb-2 block text-sm font-semibold">Doctor</label>
                        <div class="controls">
                            <select style="width: 100%" id="doctor" class="form-control select2" name="doctor" onchange="retrive_renewed_date(this.value);">
                                <option value="0" selected>--Select doctor--</option>
                                @foreach($doctors as $doctor)
                                    <option value="{{ $doctor->id }}">{{ $doctor->doctor_name }}{{ $doctor->money_rc_no ? ' (' . $doctor->money_rc_no . ')' : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="control-group mb-4" id="edit_discount_control">
                        <label class="control-label mb-2 block text-sm font-semibold">Policy no.</label>
                        <div class="controls">
                            <input type="text" id="policy_no" class="form-control" name="policy_no" autocomplete="off">
                        </div>
                    </div>

                    <div class="control-group mb-4" id="edit_discount_control">
                        <label class="control-label mb-2 block text-sm font-semibold">Last renewed date</label>
                        <div class="controls">
                            <input type="hidden" name="renew_history_id" id="renew_history_id">
                            <input type="text" id="last_renewed_date" class="form-control datepicker" name="last_renewed_date" autocomplete="off">
                        </div>
                    </div>

                    <div class="control-group mb-4" id="edit_discount_control">
                        <label class="control-label mb-2 block text-sm font-semibold">Received date</label>
                        <div class="controls">
                            <input type="text" id="rcv_date" class="form-control datepicker" name="rcv_date" autocomplete="off">
                        </div>
                    </div>

                    <div class="control-group" id="edit_discount_control">
                        <label class="control-label mb-2 block text-sm font-semibold">Policy received</label>
                        <div class="controls">
                            <input type="file" name="policy_file" id="policy_file" class="form-control">
                        </div>
                    </div>
                </fieldset>
            </div>

            <div class="modal-footer flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4">
                <a href="javascript:void(0);" class="btn btn-default" onclick="close_received_modal();">Close</a>
                <button type="submit" class="btn btn-primary" onclick="return add_received_validation();">Submit</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        function add_received_modal() {
            const modal = document.getElementById('receivedModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function close_received_modal() {
            const modal = document.getElementById('receivedModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function add_received_validation() {
            return true;
        }

        function retrive_renewed_date() {
            // Placeholder for future dynamic fetch.
        }

        if (typeof flatpickr !== 'undefined') {
            document.querySelectorAll('.datepicker').forEach(input => {
                flatpickr(input, { dateFormat: 'd/m/Y' });
            });
        }

        if (typeof $ !== 'undefined' && $.fn.select2) {
            $('.select2').select2({ width: '100%' });
        }
    </script>
@endpush
@endsection

@extends('admin.layouts.app')

@section('title', 'Policy Received')
@section('page-title', 'Doctor Enrollment Entry')

@section('content')
@php
    $draftStep3 = $draftStep3 ?? [];
    $defaultReceiveDate = old('rcv_date', data_get($draftStep3, 'rcv_date') ?: now()->format('d/m/Y'));
    $defaultLastRenewedDate = old('last_renewed_date', data_get($draftStep3, 'last_renewed_date') ?: optional($enrollment->last_renewal_date)->format('d/m/Y'));
    $defaultPolicyStartDate = old('policy_start_date', data_get($draftStep3, 'policy_start_date') ?: optional($enrollment->policy_date)->format('d/m/Y'));
    $defaultPolicyEndDate = old('policy_end_date', data_get($draftStep3, 'policy_end_date'));
    $showLastRenewedDate = filled(data_get($draftStep3, 'last_renewed_date'))
        || filled(optional($enrollment->last_renewal_date)->format('d/m/Y'))
        || filled($enrollment->renewal_date)
        || filled($enrollment->policy_no)
        || (isset($policyReceipts) && $policyReceipts->count() > 0);
@endphp

<section class="mx-auto max-w-4xl">
    <div class="mb-5 rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-600">Step 3 of doctor enrollment</p>
        <div class="mt-1 flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
            <div>
                <h2 class="text-2xl font-bold text-slate-900">Name: {{ $enrollment->doctor_name ?? '—' }}</h2>
                <p class="mt-1 text-sm text-slate-500">Submit policy received details for this enrollment.</p>
            </div>
            <div class="rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-700">
                <span class="font-semibold text-slate-900">Customer ID:</span> {{ $enrollment->customer_id_no ?? '—' }}
            </div>
        </div>
    </div>

    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-lg">
        <form action="{{ route('admin.enrollment.policy-receipt.store', $enrollment) }}" method="post" enctype="multipart/form-data" id="policy_received_form" class="space-y-6">
            @csrf
            <input type="hidden" name="doctor" value="{{ $enrollment->id }}">
            <input type="hidden" name="workflow_step" value="3">
            <input type="hidden" name="workflow_enrollment_id" id="workflow_enrollment_id_step3" value="{{ $enrollment->id }}">
            <h3 class="text-lg font-semibold">Submit Policy Received</h3>
            <div class="grid gap-5 md:grid-cols-2">
                <div class="form-group">
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Policy No</label>
                    <input type="text" name="policy_no" class="w-full rounded-xl border px-4 py-2" value="{{ old('policy_no', data_get($draftStep3, 'policy_no')) }}">
                </div>
                @if($showLastRenewedDate)
                <div class="form-group">
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Last Renewed Date</label>
                    <input type="text" name="last_renewed_date" class="w-full rounded-xl border px-4 py-2 datepicker" autocomplete="off" value="{{ $defaultLastRenewedDate }}">
                </div>
                @endif
                <div class="form-group">
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Policy Start Date</label>
                    <input type="text" name="policy_start_date" class="w-full rounded-xl border px-4 py-2 datepicker" autocomplete="off" value="{{ $defaultPolicyStartDate }}">
                </div>
                <div class="form-group">
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Policy End Date</label>
                    <input type="text" name="policy_end_date" class="w-full rounded-xl border px-4 py-2 datepicker" autocomplete="off" value="{{ $defaultPolicyEndDate }}">
                </div>
                <div class="form-group">
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Received Date</label>
                    <input type="text" name="rcv_date" class="w-full rounded-xl border px-4 py-2 datepicker" autocomplete="off" value="{{ $defaultReceiveDate }}">
                </div>
                <div class="form-group">
                    <label class="mb-2 block text-sm font-semibold text-slate-700">Policy File</label>
                    <input type="file" name="policy_file" class="block w-full">
                </div>
            </div>
            <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-5">
                <a href="{{ route('admin.enrollment.step2', $enrollment) }}" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Back</a>
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-500">
                    <i class="ri-arrow-right-line"></i>
                    <span>Save and Continue to Step 4</span>
                </button>
            </div>
        </form>

        @if(!empty($policyReceipts) && $policyReceipts->count() > 0)
            <div class="mb-6">
                <h4 class="font-semibold">Policy Received History</h4>
                <table class="w-full mt-2 table-auto text-sm">
                    <thead>
                        <tr>
                            <th class="text-left">ID</th>
                            <th class="text-left">Policy No</th>
                            <th class="text-left">Receive Date</th>
                            <th class="text-left">Files</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($policyReceipts as $pr)
                            <tr>
                                <td>{{ $pr->id }}</td>
                                <td>{{ $pr->policy_no ?? '—' }}</td>
                                <td>{{ optional($pr->receive_date)->format('d/m/Y') ?? '—' }}</td>
                                <td>@if($pr->policy_file)<a href="{{ asset('storage/' . $pr->policy_file) }}" target="_blank">Download</a>@else — @endif</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</section>

@push('scripts')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        if (typeof flatpickr !== 'undefined') {
            document.querySelectorAll('.datepicker').forEach(function (input) {
                flatpickr(input, { dateFormat: 'd/m/Y' });
            });
        }

        @php
            $autosaveUser = auth()->user();
            $skipAutosave = $autosaveUser && (
                (($autosaveUser->role ?? null) === 'super_admin')
                || (method_exists($autosaveUser, 'hasAdminRole') && $autosaveUser->hasAdminRole('super_admin'))
            );
        @endphp
        @if(!$skipAutosave)
        (function () {
            const form = document.getElementById('policy_received_form');
            const workflowField = document.getElementById('workflow_enrollment_id_step3');
            const autosaveUrl = @json(route('admin.enrollment.autosave'));
            let autosaveTimer = null;
            let autosaveQueued = false;
            let autosaveInFlight = false;

            if (!form || !workflowField) {
                return;
            }

            const scheduleAutosave = function () {
                autosaveQueued = true;
                if (autosaveTimer) {
                    clearTimeout(autosaveTimer);
                }

                autosaveTimer = setTimeout(runAutosave, 2500);
            };

            const runAutosave = function () {
                if (!autosaveQueued || autosaveInFlight) {
                    return;
                }

                autosaveQueued = false;
                autosaveInFlight = true;

                const payload = new FormData(form);
                payload.set('workflow_step', '3');
                payload.set('workflow_enrollment_id', workflowField.value || '');

                fetch(autosaveUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': form.querySelector('input[name="_token"]')?.value || '',
                        'Accept': 'application/json',
                    },
                    body: payload,
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.success && data.enrollment_id) {
                            workflowField.value = data.enrollment_id;
                        }
                    })
                    .catch(() => {})
                    .finally(() => {
                        autosaveInFlight = false;
                    });
            };

            form.addEventListener('input', scheduleAutosave, true);
            form.addEventListener('change', scheduleAutosave, true);
            setInterval(runAutosave, 25000);
        })();
        @endif
    </script>
@endpush
@endsection

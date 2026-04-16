@extends('admin.layouts.app')

@section('title', 'Doctor List')
@section('page-title', 'Doctor List')

@section('content')
<style>
    .renew-toolbar {
        background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);
        border: 1px solid #dbeafe;
    }
    .renew-btn {
        display: inline-flex;
        align-items: center;
        border-radius: 0.5rem;
        padding: 0.5rem 0.75rem;
        font-size: 0.8125rem;
        font-weight: 700;
        color: #fff;
        transition: all 0.15s ease;
    }
    .renew-btn:hover { filter: brightness(0.95); }
    .renew-btn-muted { background: #475569; }
    .renew-btn-green { background: #15803d; }
    .renew-btn-teal { background: #0f766e; }
    .renew-btn-blue { background: #1d4ed8; }
    .renew-heading {
        color: #0f172a;
        letter-spacing: 0.01em;
    }
    .renew-table {
        border-collapse: separate;
        border-spacing: 0;
        border: 1px solid #dbe3ee;
        border-radius: 0.85rem;
        overflow: hidden;
    }
    .renew-table thead th {
        background: #0f172a;
        color: #e2e8f0;
        font-size: 0.74rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        border-bottom: 1px solid #1e293b;
    }
    .renew-table tbody td {
        vertical-align: top;
        border-bottom: 1px solid #e2e8f0;
        color: #0f172a;
        font-size: 0.82rem;
    }
    .renew-table tbody tr:nth-child(even) { background: #f8fafc; }
    .renew-table tbody tr:hover { background: #eef6ff; }
    .renew-pill {
        display: inline-flex;
        align-items: center;
        border-radius: 9999px;
        padding: 0.15rem 0.5rem;
        font-size: 0.7rem;
        font-weight: 700;
        line-height: 1;
    }
    .renew-pill-active { background: #d1fae5; color: #065f46; }
    .renew-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.35rem;
        padding: 0.3rem 0.45rem;
        color: #fff;
        font-size: 0.75rem;
        font-weight: 700;
    }
</style>

<div class="section-card">
    <div class="renew-toolbar mb-5 rounded-xl p-4">
        <form method="GET" action="{{ route('admin.doctors.index') }}" id="search_form" class="space-y-3">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-12">
                <div class="md:col-span-3">
                    <select name="renew_type" id="renew_type" class="w-full rounded-lg border-slate-300 text-sm" required>
                        <option value="">---Select renew type---</option>
                        <option value="upcoming_renewed" {{ $renewType === 'upcoming_renewed' ? 'selected' : '' }}>Next Renewal</option>
                        <option value="renewed" {{ $renewType === 'renewed' ? 'selected' : '' }}>Renewed</option>
                    </select>
                </div>
                <div class="md:col-span-3">
                    <select name="search_month" id="search_month" class="w-full rounded-lg border-slate-300 text-sm">
                        <option value="">---Select Month---</option>
                        @foreach($months as $month)
                            <option value="{{ $month }}" {{ $searchMonth === $month ? 'selected' : '' }}>{{ $month }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-3">
                    <select name="search_year" id="search_year" class="w-full rounded-lg border-slate-300 text-sm">
                        <option value="">---Select Year---</option>
                        @foreach($years as $year)
                            <option value="{{ $year }}" {{ (string) $searchYear === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-3 flex gap-2">
                    <button type="submit" class="btn-brand !px-4 !py-2 text-sm">Search</button>
                    <a href="{{ route('admin.doctors.index') }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Reset</a>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.doctors.incomplete-documents') }}" class="renew-btn renew-btn-green">
                    Doctors with incomplete document
                </a>

                <button type="button" class="renew-btn renew-btn-muted" onclick="window.print()" title="Print All">
                    <i class="ri-printer-line mr-1"></i>
                    Print
                </button>

                <a href="{{ route('admin.doctors.csv-report', request()->query()) }}" class="renew-btn renew-btn-teal" title="Export CSV">
                    Export CSV
                </a>

                <a href="{{ route('admin.enrollment.create') }}" class="renew-btn renew-btn-blue">
                    <i class="ri-user-add-line mr-1"></i>
                    New Enrollment
                </a>
            </div>
        </form>
    </div>

    <div class="mb-3 flex items-center justify-between">
        <h3 class="renew-heading section-title mb-0">Doctor Renewal List</h3>
        @if(!empty($showIncompleteOnly))
            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">Showing incomplete document records</span>
        @endif
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-2 text-sm font-semibold text-slate-700">Search:</div>

    <div class="overflow-x-auto">
        <table class="renew-table data-table table table-striped table-bordered bootstrap-datatable">
            <thead>
                <tr>
                    <th>SL No</th>
                    <th>Name/Phone No</th>
                    <th>Speciality &amp; Plan</th>
                    <th>Degree &amp; Reg/Year</th>
                    <th>Policy No/Membership No</th>
                    <th>Insurance Cov/Legal Service</th>
                    <th>Insurance amount</th>
                    <th>Medeforum Amount</th>
                    <th>Last Renewed DT</th>
                    <th>Next Renewal DT</th>
                    <th>Marketing staff name/Phone No.</th>
                    <th>Auto email</th>
                    <th>Auto SMS</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($enrollments as $enr)
                    <tr>
                        <td>{{ $enrollments->firstItem() + $loop->index }}</td>
                        <td>
                            <div class="font-semibold text-slate-800">{{ $enr->doctor_name ?? '—' }}</div>
                            <div class="text-xs text-slate-500">{{ $enr->mobile1 ?? '—' }}</div>
                        </td>
                        <td>
                            @php
                                $planLabel = match((int)$enr->plan) { 1 => 'Normal', 2 => 'High Risk', 3 => 'Combo', default => '—' };
                            @endphp
                            <div>{{ $enr->specialization->name ?? '—' }}</div>
                            <div class="text-xs text-slate-500">{{ $planLabel }}</div>
                        </td>
                        <td>
                            <div>{{ $enr->qualification ?? '—' }}</div>
                            <div class="text-xs text-slate-500">{{ $enr->medical_registration_no ?? '—' }} / {{ $enr->year_of_reg ?? '—' }}</div>
                        </td>
                        <td>
                            <div>{{ $enr->money_rc_no ?? '—' }}</div>
                            <div class="text-xs text-slate-500">{{ $enr->customer_id_no ?? '—' }}</div>
                        </td>
                        <td>
                            <div>Coverage ID: {{ $enr->coverage_id ?? '—' }}</div>
                            <div class="text-xs text-slate-500">Legal Service: {{ $enr->service_amount ? '₹' . number_format($enr->service_amount, 2) : '—' }}</div>
                        </td>
                        <td>{{ $enr->payment_amount ? '₹' . number_format($enr->payment_amount, 2) : '—' }}</td>
                        <td>{{ $enr->service_amount ? '₹' . number_format($enr->service_amount, 2) : '—' }}</td>
                        <td>{{ optional($enr->created_at)->format('d M Y') ?? '—' }}</td>
                        <td>{{ optional($enr->created_at)->copy()->addYear()->format('d M Y') }}</td>
                        <td>
                            <div>{{ $enr->agent_name ?? '—' }}</div>
                            <div class="text-xs text-slate-500">{{ $enr->agent_phone_no ?? '—' }}</div>
                        </td>
                        <td>
                            @if($enr->bond_to_mail)
                                <span class="rounded bg-green-100 px-2 py-1 text-xs font-semibold text-green-700">Enabled</span>
                            @else
                                <span class="rounded bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600">Disabled</span>
                            @endif
                        </td>
                        <td>
                            @if(!empty($enr->mobile1))
                                <span class="rounded bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-700">Ready</span>
                            @else
                                <span class="rounded bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600">No Mobile</span>
                            @endif
                        </td>
                        <td class="actions-cell">
                            <div class="flex flex-wrap gap-1">
                                <a href="{{ route('admin.doctors.show', $enr->id) }}" class="renew-action bg-emerald-600" title="View" onclick="event.stopPropagation();">
                                    <i class="ri-eye-line"></i>
                                </a>
                                <a href="{{ route('admin.enrollment.edit', $enr->id) }}" class="renew-action bg-slate-700" title="Edit" onclick="event.stopPropagation();">
                                    <i class="ri-pencil-line"></i>
                                </a>
                                <a target="_blank" href="{{ route('admin.doctors.show', $enr->id) }}?tab=doctor_documents" class="renew-action bg-blue-600" title="Document" onclick="event.stopPropagation();">
                                    <i class="ri-file-line"></i>
                                </a>
                                <button type="button" class="renew-action bg-red-600" title="Renew" onclick="event.stopPropagation(); renewDoctor({{ $enr->id }})">R</button>
                                <button type="button" class="renew-action bg-cyan-600" title="Send mail" onclick="event.stopPropagation(); sendMail({{ $enr->id }}, '{{ $enr->doctor_email }}')"><i class="ri-mail-line"></i></button>
                                <button type="button" class="renew-action bg-sky-600" title="Send SMS" onclick="event.stopPropagation(); sendSms({{ $enr->id }}, '{{ $enr->mobile1 }}')"><i class="ri-message-2-line"></i></button>
                                <button type="button" class="renew-action bg-green-700" title="Resend bond" onclick="event.stopPropagation(); resendBond({{ $enr->id }}, '{{ $enr->doctor_email }}')"><i class="ri-send-plane-line"></i></button>
                                <button type="button" class="renew-action bg-indigo-600" title="Resend money receipt" onclick="event.stopPropagation(); resendReceipt({{ $enr->id }}, '{{ $enr->doctor_email }}')"><i class="ri-mail-send-line"></i></button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td><b>1</b></td>

                        <td>
                            <b>
                                <a href="javascript:void(0);" class="text-blue-700 hover:underline">DR.IMTIAZ AHMED</a>
                                /9434163237
                            </b>
                            <br>
                            <label class="renew-pill renew-pill-active">Renewal</label>
                        </td>

                        <td><b>Eye Surgeon <br>(Ophthalmologist) <br>(PLAN-CA)/<br>Combo</b></td>
                        <td><b>MBBS/DOMS/MS/<br>45420/<br>1987</b></td>
                        <td><b>0310002725P117184932(2026-27)/<br>IND-19000786-M-0001</b></td>
                        <td><b>30 Lakh</b></td>
                        <td class="break-words" style="max-width: 80px;"><b>Rs.5664/-</b></td>
                        <td><b>Rs.14797/-</b></td>
                        <td><b>04/02/2026</b></td>
                        <td><b>04/02/2027</b></td>
                        <td><b>SUPARNA BISWAS/<br>9681203303</b></td>

                        <td>
                            <span title="Auto email disabled">
                                <i class="ri-close-circle-fill text-red-600" style="font-size: 1.2rem;" aria-hidden="true"></i>
                            </span>
                        </td>

                        <td>
                            <span title="Auto SMS disabled">
                                <i class="ri-close-circle-fill text-red-600" style="font-size: 1.2rem;" aria-hidden="true"></i>
                            </span>
                        </td>

                        <td>
                            <div class="flex flex-wrap gap-1">
                                <a class="renew-action bg-emerald-600" title="View" href="javascript:void(0);"><i class="ri-eye-line"></i></a>
                                <a class="renew-action bg-slate-700" title="Edit" href="javascript:void(0);"><i class="ri-pencil-line"></i></a>
                                <a class="renew-action bg-blue-600" title="Document" href="javascript:void(0);"><i class="ri-file-line"></i></a>
                                <a class="renew-action bg-red-600" title="Renew" href="javascript:void(0);">R</a>
                                <a class="renew-action bg-cyan-600" title="Send mail" href="javascript:void(0);"><i class="ri-mail-line"></i></a>
                                <a class="renew-action bg-sky-600" title="Send SMS" href="javascript:void(0);"><i class="ri-message-2-line"></i></a>
                                <a class="renew-action bg-green-700" title="Resend bond" href="javascript:void(0);"><i class="ri-send-plane-line"></i></a>
                                <a class="renew-action bg-indigo-600" title="Resend money receipt" href="javascript:void(0);"><i class="ri-mail-send-line"></i></a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($enrollments->hasPages())
        <div class="mt-4">{{ $enrollments->links() }}</div>
    @endif
</div>

<script>
const csrfToken = '{{ csrf_token() }}';

function renewDoctor(doctorId) {
    if (confirm('Are you sure you want to renew this doctor?')) {
        alert('Renewal functionality to be implemented');
    }
}

function sendMail(doctorId, email) {
    if (!email) {
        alert('No email address on file for this doctor.');
        return;
    }
    fetch(`/admin/doctors/${doctorId}/send-mail`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json'
        }
    })
    .then(r => r.json())
    .then(data => alert(data.message))
    .catch(err => alert('Error: ' + err.message));
}

function sendSms(doctorId, phone) {
    if (!phone) {
        alert('No phone number on file for this doctor.');
        return;
    }
    fetch(`/admin/doctors/${doctorId}/send-sms`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json'
        }
    })
    .then(r => r.json())
    .then(data => alert(data.message))
    .catch(err => alert('Error: ' + err.message));
}

function resendBond(doctorId, email) {
    if (!email) { alert('No email address on file for this doctor.'); return; }
    fetch(`/admin/doctors/${doctorId}/resend-bond`, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' } })
    .then(r => r.json()).then(data => alert(data.message)).catch(err => alert('Error: ' + err.message));
}

function resendReceipt(doctorId, email) {
    if (!email) { alert('No email address on file for this doctor.'); return; }
    fetch(`/admin/doctors/${doctorId}/resend-receipt`, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' } })
    .then(r => r.json()).then(data => alert(data.message)).catch(err => alert('Error: ' + err.message));
}
</script>
@endsection

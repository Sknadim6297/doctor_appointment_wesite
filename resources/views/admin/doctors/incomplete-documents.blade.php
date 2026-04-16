@extends('admin.layouts.app')

@section('title', 'Incomplete Doctor Documents')
@section('page-title', 'Doctor Management')

@section('content')
<style>
    .doc-shell {
        background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
        border: 1px solid #dbe3ee;
        border-radius: 1rem;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }
    .doc-banner {
        background: linear-gradient(135deg, #fff7ed 0%, #fffbeb 100%);
        border: 1px solid #fed7aa;
        color: #7c2d12;
    }
    .doc-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        border-radius: 0.55rem;
        padding: 0.5rem 0.8rem;
        font-size: 0.78rem;
        font-weight: 700;
        line-height: 1;
        text-decoration: none;
        transition: transform 0.15s ease, filter 0.15s ease;
    }
    .doc-btn:hover { transform: translateY(-1px); filter: brightness(0.98); }
    .doc-btn-view { background: #10b981; color: #fff; }
    .doc-btn-edit { background: #475569; color: #fff; }
    .doc-btn-doc { background: #2563eb; color: #fff; }
    .doc-btn-renew { background: #dc2626; color: #fff; }
    .doc-btn-toggle { background: transparent; border: 0; padding: 0; cursor: pointer; }
    .doc-table {
        border-collapse: separate;
        border-spacing: 0;
        border: 1px solid #dbe3ee;
        border-radius: 0.95rem;
        overflow: hidden;
        min-width: 1500px;
    }
    .doc-table thead th {
        background: #0f172a;
        color: #e2e8f0;
        font-size: 0.72rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        padding: 0.8rem 0.7rem;
        border-bottom: 1px solid #1e293b;
        white-space: nowrap;
    }
    .doc-table tbody td {
        font-size: 0.8rem;
        color: #0f172a;
        vertical-align: middle;
        padding: 0.8rem 0.7rem;
        border-bottom: 1px solid #e2e8f0;
    }
    .doc-table tbody tr:nth-child(even) { background: #f8fafc; }
    .doc-table tbody tr:hover { background: #eff6ff; }
    .doc-pill {
        display: inline-flex;
        align-items: center;
        border-radius: 9999px;
        padding: 0.25rem 0.6rem;
        font-size: 0.7rem;
        font-weight: 800;
        line-height: 1;
    }
    .doc-pill-normal { background: #dbeafe; color: #1d4ed8; }
    .doc-pill-high { background: #fef3c7; color: #92400e; }
    .doc-pill-combo { background: #ede9fe; color: #6d28d9; }
    .doc-pill-danger { background: #fee2e2; color: #b91c1c; }
    .doc-filter {
        background: #fff;
        border: 1px solid #dbe3ee;
        border-radius: 0.95rem;
        padding: 1rem;
    }
    .doc-filter select,
    .doc-filter input {
        height: 40px;
        border-radius: 0.6rem;
        border-color: #cbd5e1;
        font-size: 0.85rem;
    }
    @media print {
        .no-print {
            display: none !important;
        }
        body {
            background: #fff !important;
        }
        .doc-shell {
            border: 0;
            box-shadow: none;
            padding: 0;
        }
        .doc-table {
            min-width: 0;
        }
    }
</style>

<div class="section-card space-y-5">
    <div class="doc-shell p-5 md:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between no-print">
            <div>
                <h3 class="section-title mb-2">Doctor List - Incomplete Documents</h3>
                <p class="text-sm text-slate-600">
                    Records in this section are missing one or more required documents, such as Aadhaar, PAN, or medical registration.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.enrollment.create') }}" class="doc-btn doc-btn-view">
                    <i class="ri-user-add-line"></i>
                    New Enrollment
                </a>
                <a href="{{ route('admin.enrollment') }}" class="doc-btn doc-btn-doc">
                    <i class="ri-list-check-2"></i>
                    Doctor List
                </a>
                <a href="{{ route('admin.doctors.incomplete-documents') }}" class="doc-btn doc-btn-renew">
                    <i class="ri-refresh-line"></i>
                    Refresh
                </a>
            </div>
        </div>

        <div class="doc-filter mt-5 no-print">
            <form method="GET" action="{{ route('admin.doctors.incomplete-documents') }}" class="space-y-4">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-12 md:items-end">
                    <div class="md:col-span-4 lg:col-span-3">
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500" for="search_month">Search Month</label>
                        <select name="search_month" id="search_month" class="w-full">
                            <option value="0">---Select Month---</option>
                            @foreach(['January','February','March','April','May','June','July','August','September','October','November','December'] as $monthName)
                                <option value="{{ $monthName }}" {{ request('search_month', '0') === $monthName ? 'selected' : '' }}>{{ $monthName }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-4 lg:col-span-3">
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wide text-slate-500" for="search_year">Search Year</label>
                        <select name="search_year" id="search_year" class="w-full">
                            <option value="0">---Select Year---</option>
                            @for($year = 2000; $year <= 2036; $year++)
                                <option value="{{ $year }}" {{ (string) request('search_year', '0') === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="md:col-span-4 lg:col-span-3 flex flex-wrap gap-2">
                        <button type="submit" class="doc-btn doc-btn-view px-4">
                            <i class="ri-search-line"></i>
                            Search
                        </button>
                        <a href="{{ route('admin.doctors.incomplete-documents') }}" class="doc-btn doc-btn-doc px-4">
                            <i class="ri-refresh-line"></i>
                            Reset
                        </a>
                        <button type="button" class="doc-btn doc-btn-renew px-4" onclick="print_data()">
                            <i class="ri-printer-line"></i>
                            Print All
                        </button>
                    </div>
                </div>
            </form>
        </div>

        @if(session('success'))
            <div class="doc-banner mt-4 rounded-xl px-4 py-3 text-sm font-semibold">
                {{ session('success') }}
            </div>
        @endif

        <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            Showing only doctors with incomplete document records. Use the action buttons to review details, edit enrollment, or resend documents.
        </div>

        <div class="mt-5 overflow-x-auto">
            <table class="doc-table w-full">
                <thead>
                    <tr>
                        <th style="width: 42px;"><input type="checkbox" name="all_chk" id="all_chk" onclick="check_all()"></th>
                        <th style="width: 60px;">SL No</th>
                        <th>Name / Phone No</th>
                        <th>Speciality &amp; Plan</th>
                        <th>Degree &amp; Reg/Year</th>
                        <th>Policy No / Membership No</th>
                        <th>Insurance Cov / Legal Service</th>
                        <th>Insurance amount</th>
                        <th>Medeforum Amount</th>
                        <th>Enrollment DT</th>
                        <th>Renewal DT</th>
                        <th>Marketing staff name / Phone No.</th>
                        <th>Auto email</th>
                        <th>Auto SMS</th>
                        <th style="min-width: 280px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($doctors as $doctor)
                        @php
                            $planLabel = match((int) $doctor->plan) {
                                1 => 'Normal',
                                2 => 'High Risk',
                                3 => 'Combo',
                                default => $doctor->plan_name ?: '—',
                            };
                            $planClass = match((int) $doctor->plan) {
                                1 => 'doc-pill-normal',
                                2 => 'doc-pill-high',
                                3 => 'doc-pill-combo',
                                default => 'doc-pill-danger',
                            };
                            $renewalDate = $doctor->created_at?->copy()->addYear();
                            $qualificationYear = is_array($doctor->qualification_year)
                                ? implode(', ', array_filter($doctor->qualification_year))
                                : ($doctor->qualification_year ?? '—');
                            $degreeParts = array_filter([
                                $doctor->qualification,
                                $doctor->medical_registration_no,
                                $qualificationYear,
                            ]);
                            $degreeBlock = $degreeParts ? implode('/<br>', $degreeParts) : '—';
                        @endphp
                        <tr>
                            <td><input type="checkbox" name="record" value="{{ $doctor->id }}"></td>
                            <td><b>{{ $doctors->firstItem() + $loop->index }}</b></td>
                            <td>
                                <b>
                                    <a href="{{ route('admin.doctors.show', $doctor->id) }}" target="_blank">{{ $doctor->doctor_name ?? '—' }}</a><br>
                                    {{ $doctor->mobile1 ?? '—' }}
                                </b>
                            </td>
                            <td>
                                <b>
                                    {{ $doctor->specialization->name ?? '—' }}<br>
                                    (<span class="doc-pill {{ $planClass }}">{{ $planLabel }}</span>)
                                </b>
                            </td>
                            <td><b>{!! $degreeBlock !!}</b></td>
                            <td><b>{{ $doctor->customer_id_no ?? '—' }}</b></td>
                            <td><b>{{ $doctor->payment_mode ?? '—' }}</b></td>
                            <td><b>{{ filled($doctor->payment_amount) ? 'Rs.' . number_format((float) $doctor->payment_amount, 0) . '/-' : '—' }}</b></td>
                            <td><b>{{ filled($doctor->service_amount) ? 'Rs.' . number_format((float) $doctor->service_amount, 0) . '/-' : '—' }}</b></td>
                            <td><b>{{ optional($doctor->created_at)->format('d/m/Y') ?? '—' }}</b></td>
                            <td><b>{{ $renewalDate?->format('d/m/Y') ?? '—' }}</b></td>
                            <td><b>{{ $doctor->agent_name ?? '—' }}<br>{{ $doctor->agent_phone_no ?? '—' }}</b></td>
                            <td class="text-center">
                                <button type="button" class="doc-btn-toggle" data-enabled="{{ $doctor->bond_to_mail ? 1 : 0 }}" onclick="change_auto_mail_status({{ $doctor->id }})" id="auto_mail-{{ $doctor->id }}" title="Toggle auto email">
                                    <i class="ri-{{ $doctor->bond_to_mail ? 'checkbox-circle-fill' : 'close-circle-fill' }}" style="color: {{ $doctor->bond_to_mail ? 'green' : 'red' }}; font-size: 2.2em; cursor: pointer;"></i>
                                </button>
                            </td>
                            <td class="text-center">
                                <button type="button" class="doc-btn-toggle" data-enabled="{{ $doctor->auto_sms_enabled ? 1 : 0 }}" onclick="change_auto_sms_status({{ $doctor->id }})" id="auto_sms-{{ $doctor->id }}" title="Toggle auto SMS">
                                    <i class="ri-{{ $doctor->auto_sms_enabled ? 'checkbox-circle-fill' : 'close-circle-fill' }}" style="color: {{ $doctor->auto_sms_enabled ? 'green' : 'red' }}; font-size: 2.2em; cursor: pointer;"></i>
                                </button>
                            </td>
                            <td>
                                <div class="flex flex-wrap gap-1">
                                    <a href="{{ route('admin.doctors.show', $doctor->id) }}" class="doc-btn doc-btn-view" title="View">
                                        <i class="ri-eye-line"></i>
                                    </a>
                                    <a href="{{ route('admin.enrollment.legacy-edit', $doctor->id) }}" class="doc-btn doc-btn-edit" title="Edit">
                                        <i class="ri-edit-2-line"></i>
                                    </a>
                                    <a target="_blank" href="{{ route('admin.doctors.show', $doctor->id) }}?tab=doctor_documents" class="doc-btn doc-btn-doc" title="Document">
                                        <i class="ri-file-line"></i>
                                    </a>
                                    <a href="{{ route('admin.enrollment.legacy-renewal', ['doctor' => $doctor->id, 'renewType' => 'renewal']) }}" class="doc-btn doc-btn-renew" title="Renew">
                                        <b style="font-size: 1.05em;">R</b>
                                    </a>
                                    <button type="button" class="doc-btn doc-btn-doc" title="Send mail" onclick="sendMail({{ $doctor->id }}, @js($doctor->doctor_email))">
                                        <i class="ri-mail-line"></i>
                                    </button>
                                    <button type="button" class="doc-btn doc-btn-doc" title="Send SMS" onclick="sendSms({{ $doctor->id }}, @js($doctor->mobile1))">
                                        <i class="ri-message-2-line"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="15" class="py-8 text-center text-slate-500">
                                No incomplete document records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($doctors->hasPages())
            <div class="mt-6">
                {{ $doctors->links() }}
            </div>
        @endif
    </div>
</div>

<script>
const csrfToken = '{{ csrf_token() }}';

function check_all() {
    const master = document.getElementById('all_chk');
    document.querySelectorAll('input[name="record"]').forEach(function (checkbox) {
        checkbox.checked = master.checked;
    });
}

function print_data() {
    window.print();
}

function change_auto_mail_status(doctorId) {
    const button = document.getElementById(`auto_mail-${doctorId}`);
    const currentEnabled = button.dataset.enabled === '1';

    fetch(`/admin/doctors/${doctorId}/toggle-auto-email`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ enabled: !currentEnabled })
    })
    .then(function (response) { return response.json(); })
    .then(function (data) {
        alert(data.message);
        window.location.reload();
    })
    .catch(function (error) {
        alert('Error: ' + error.message);
    });
}

function change_auto_sms_status(doctorId) {
    const button = document.getElementById(`auto_sms-${doctorId}`);
    const currentEnabled = button.dataset.enabled === '1';

    fetch(`/admin/doctors/${doctorId}/toggle-auto-sms`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ enabled: !currentEnabled })
    })
    .then(function (response) { return response.json(); })
    .then(function (data) {
        alert(data.message);
        window.location.reload();
    })
    .catch(function (error) {
        alert('Error: ' + error.message);
    });
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
    .then(function (response) { return response.json(); })
    .then(function (data) { alert(data.message); })
    .catch(function (error) { alert('Error: ' + error.message); });
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
    .then(function (response) { return response.json(); })
    .then(function (data) { alert(data.message); })
    .catch(function (error) { alert('Error: ' + error.message); });
}
</script>
@endsection
@extends('admin.layouts.app')

@section('title', 'Doctor List')
@section('page-title', 'Doctor Management')

@section('content')
<style>
    .doctor-toolbar {
        background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);
        border: 1px solid #dbeafe;
    }
    .doctor-btn {
        display: inline-flex;
        align-items: center;
        border-radius: 0.5rem;
        padding: 0.5rem 0.75rem;
        font-size: 0.8125rem;
        font-weight: 700;
        color: #fff;
        transition: all 0.15s ease;
    }
    .doctor-btn:hover { filter: brightness(0.95); }
    .doctor-btn-blue { background: #1d4ed8; }
    .doctor-btn-amber { background: #d97706; }
    .doctor-heading {
        color: #0f172a;
        letter-spacing: 0.01em;
    }
    .doctor-table {
        border-collapse: separate;
        border-spacing: 0;
        border: 1px solid #dbe3ee;
        border-radius: 0.85rem;
        overflow: visible;
    }
    .doctor-table thead th {
        background: #0f172a;
        color: #e2e8f0;
        font-size: 0.74rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        padding: 0.75rem;
        border-bottom: 1px solid #1e293b;
    }
    .doctor-table tbody td {
        vertical-align: middle;
        border-bottom: 1px solid #e2e8f0;
        color: #0f172a;
        font-size: 0.82rem;
        padding: 0.75rem;
    }
    .doctor-table tbody tr:nth-child(even) { background: #f8fafc; }
    .doctor-table tbody tr:hover { background: #eef6ff; }
    .doctor-pill {
        display: inline-flex;
        align-items: center;
        border-radius: 9999px;
        padding: 0.25rem 0.6rem;
        font-size: 0.7rem;
        font-weight: 700;
        line-height: 1;
    }
    .doctor-pill-renewal { background: #dbeafe; color: #0369a1; }
    .doctor-pill-upcoming { background: #fef08a; color: #78350f; }
    .doctor-pill-due { background: #fecaca; color: #7c2d12; }
    .doctor-action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.35rem;
        padding: 0.35rem 0.5rem;
        color: #fff;
        font-size: 0.75rem;
        font-weight: 700;
        text-decoration: none;
        transition: all 0.2s ease;
        border: none;
        cursor: pointer;
    }
    .doctor-action-btn:hover { transform: translateY(-2px); }
    .doctor-action-btn-view { background: #10b981; }
    .doctor-action-btn-edit { background: #6b7280; }
    .doctor-action-btn-doc { background: #3b82f6; }
    .doctor-action-btn-renew { background: #ef4444; }
    .doctor-action-btn-mail { background: #06b6d4; }
    .doctor-action-btn-sms { background: #0ea5e9; }
    .doctor-action-btn-bond { background: #22c55e; }
    .doctor-action-btn-receipt { background: #8b5cf6; }
    .doctor-action-btn { min-width: 36px; height: 34px; padding: 0.25rem; }
    .doctor-table td.actions-cell { overflow: visible; white-space: nowrap; }
</style>

<div class="section-card">
    {{-- Search & Filter Toolbar --}}
    <div class="doctor-toolbar mb-5 rounded-xl p-4">
        <form method="GET" action="{{ route('admin.doctors.index') }}" id="search_form" class="space-y-3">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-12">
                <div class="md:col-span-3">
                    <input type="text" name="search" placeholder="Search by name, email, phone..." 
                           value="{{ request('search') }}" 
                           class="w-full rounded-lg border-slate-300 text-sm">
                </div>
                <div class="md:col-span-2">
                    <select name="specialization_id" class="w-full rounded-lg border-slate-300 text-sm">
                        <option value="">— All Specializations —</option>
                        @foreach($specializations as $spec)
                            <option value="{{ $spec->id }}" {{ request('specialization_id') == $spec->id ? 'selected' : '' }}>
                                {{ $spec->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <select name="plan" class="w-full rounded-lg border-slate-300 text-sm">
                        <option value="">— All Plans —</option>
                        @foreach($plans as $id => $name)
                            <option value="{{ $id }}" {{ request('plan') == $id ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <select name="renewal_status" class="w-full rounded-lg border-slate-300 text-sm">
                        <option value="">— Renewal Status —</option>
                        <option value="upcoming" {{ request('renewal_status') === 'upcoming' ? 'selected' : '' }}>Upcoming</option>
                        <option value="overdue" {{ request('renewal_status') === 'overdue' ? 'selected' : '' }}>Overdue</option>
                    </select>
                </div>
                <div class="md:col-span-3 flex gap-2">
                    <button type="submit" class="btn-brand !px-4 !py-2 text-sm">Search</button>
                    <a href="{{ route('admin.doctors.index') }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Reset</a>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.enrollment.create') }}" class="doctor-btn doctor-btn-blue">
                    <i class="ri-user-add-line mr-1"></i>
                    New Enrollment
                </a>
                <a href="{{ route('admin.doctors.incomplete-documents') }}" class="doctor-btn doctor-btn-amber">
                    <i class="ri-file-warning-line mr-1"></i>
                    Incomplete Docs
                </a>
            </div>
        </form>
    </div>

    {{-- Page Title & Success Message --}}
    <div class="mb-3 flex items-center justify-between">
        <h3 class="doctor-heading section-title mb-0">Doctor List ({{ $doctors->total() }} total)</h3>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
            {{ session('success') }}
        </div>
    @endif

    @include('admin.doctors.partials.list-table')


    {{-- Pagination --}}
    @if($doctors->hasPages())
        <div class="mt-6">{{ $doctors->links() }}</div>
    @endif
</div>

<script>
const csrfToken = '{{ csrf_token() }}';

/** Open money receipt form on the dedicated receipts page (not inline on Doctor List). */
function renewDoctor(doctorId) {
    const baseUrl = @json(route('admin.receipts'));
    window.location.href = baseUrl + '?renew_doctor=' + encodeURIComponent(doctorId);
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
        },
        body: JSON.stringify({ email })
    })
    .then(r => r.json())
    .then(data => {
        if (data && data.message) alert(data.message);
        else alert('Email send request completed.');
    })
    .catch(err => alert('Error: ' + (err.message || err)));
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
        },
        body: JSON.stringify({ phone })
    })
    .then(r => r.json())
    .then(data => {
        if (data && data.message) alert(data.message);
        else alert('SMS send request completed.');
    })
    .catch(err => alert('Error: ' + (err.message || err)));
}

function resendBond(doctorId, email) {
    if (!email) {
        alert('No email address on file for this doctor.');
        return;
    }
    fetch(`/admin/doctors/${doctorId}/resend-bond`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ email })
    })
    .then(r => r.json())
    .then(data => {
        if (data && data.message) alert(data.message);
        else alert('Resend bond request completed.');
    })
    .catch(err => alert('Error: ' + (err.message || err)));
}

function resendReceipt(doctorId, email) {
    if (!email) {
        alert('No email address on file for this doctor.');
        return;
    }
    fetch(`/admin/doctors/${doctorId}/resend-receipt`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ email })
    })
    .then(r => r.json())
    .then(data => {
        if (data && data.message) alert(data.message);
        else alert('Resend receipt request completed.');
    })
    .catch(err => alert('Error: ' + (err.message || err)));
}
</script>
@endsection

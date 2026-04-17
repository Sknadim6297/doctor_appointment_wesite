@extends('admin.layouts.app')

@section('title', 'Membership Numbers')
@section('page-title', 'Doctor Management')

@section('content')
<style>
    .membership-toolbar {
        background: linear-gradient(135deg, #f8fafc 0%, #eef2ff 100%);
        border: 1px solid #dbeafe;
    }
    .membership-table {
        border-collapse: separate;
        border-spacing: 0;
        border: 1px solid #dbe3ee;
        border-radius: 0.85rem;
        overflow: hidden;
    }
    .membership-table thead th {
        background: #0f172a;
        color: #e2e8f0;
        font-size: 0.74rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        padding: 0.75rem;
    }
    .membership-table tbody td {
        vertical-align: middle;
        border-bottom: 1px solid #e2e8f0;
        color: #0f172a;
        font-size: 0.83rem;
        padding: 0.75rem;
    }
    .membership-table tbody tr:nth-child(even) {
        background: #f8fafc;
    }
    .membership-table tbody tr:hover {
        background: #eef6ff;
    }
    .membership-action {
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
    }
    .membership-action:hover {
        transform: translateY(-2px);
    }
    .membership-action-view {
        background: #10b981;
    }
    .membership-action-edit {
        background: #6b7280;
    }
</style>

<div class="section-card">
    <div class="membership-toolbar mb-5 rounded-xl p-4">
        <form method="GET" action="{{ route('admin.doctors.membership-nos') }}" class="grid grid-cols-1 gap-3 md:grid-cols-12">
            <div class="md:col-span-8">
                <input
                    type="text"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Search by doctor name, phone, membership no"
                    class="w-full rounded-lg border-slate-300 text-sm"
                >
            </div>
            <div class="md:col-span-4 flex gap-2">
                <button type="submit" class="btn-brand !px-4 !py-2 text-sm">Search</button>
                <a href="{{ route('admin.doctors.membership-nos') }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Reset</a>
            </div>
        </form>
    </div>

    <div class="mb-3 flex items-center justify-between">
        <h3 class="section-title mb-0">Membership numbers ({{ $memberships->total() }})</h3>
    </div>

    <div class="overflow-x-auto">
        <table class="membership-table w-full">
            <thead>
                <tr>
                    <th style="width: 70px;">SL No</th>
                    <th>Name/Phone No</th>
                    <th style="width: 250px;">Membership No</th>
                    <th style="width: 140px;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($memberships as $member)
                    <tr>
                        <td><b>{{ $memberships->firstItem() + $loop->index }}</b></td>
                        <td>
                            <b>
                                <a href="{{ route('admin.doctors.show', $member->id) }}" target="_blank" class="text-blue-700 hover:underline">
                                    {{ $member->doctor_name ?? 'N/A' }}
                                </a>
                                /{{ $member->mobile1 ?? 'N/A' }}
                            </b>
                        </td>
                        <td><b>{{ $member->customer_id_no ?? 'N/A' }}</b></td>
                        <td>
                            <div class="flex items-center gap-1">
                                <a class="membership-action membership-action-view" title="View" href="{{ route('admin.doctors.show', $member->id) }}">
                                    <i class="ri-eye-line"></i>
                                </a>
                                <a class="membership-action membership-action-edit" title="Edit" href="{{ route('admin.enrollment.legacy-edit', $member->id) }}">
                                    <i class="ri-pencil-line"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-slate-500 py-8">No data available in table</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($memberships->hasPages())
        <div class="mt-6">{{ $memberships->links() }}</div>
    @endif
</div>
@endsection

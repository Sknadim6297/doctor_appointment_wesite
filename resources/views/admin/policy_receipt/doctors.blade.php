@extends('admin.layouts.app')

@section('title', 'Doctors policy')
@section('page-title', 'Doctors policy')

@section('content')
<section class="section-card">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <h3 class="section-title mb-0">Doctors policy ({{ $policies->total() }})</h3>
        <form method="GET" action="{{ route('admin.policy-receipt.doctors') }}" id="search_form" class="flex flex-wrap items-center gap-2">
            <select name="search_year" id="search_year" class="form-control input-sm min-w-[160px]">
                <option value="0">---Select Year---</option>
                @foreach($years as $year)
                    <option value="{{ $year }}" {{ (int) $searchYear === (int) $year ? 'selected' : '' }}>{{ $year }}</option>
                @endforeach
            </select>
            <input
                type="text"
                id="search"
                name="search"
                class="master-search-input"
                value="{{ $searchText }}"
                placeholder="Search doctor or policy no."
            >
            <button type="submit" class="btn btn-primary">Search</button>
            @if((int)$searchYear > 0 || !empty($searchText))
                <a href="{{ route('admin.policy-receipt.doctors') }}" class="btn btn-default">Clear</a>
            @endif
        </form>
    </div>

    <div class="overflow-x-auto">
        <table id="example1" class="data-table">
            <thead>
                <tr>
                    <th>SL No</th>
                    <th>DOCTOR</th>
                    <th>POLICY NO.</th>
                    <th>Policy Year</th>
                </tr>
            </thead>
            <tbody>
                @forelse($policies as $policy)
                    @php
                        $policyYear = '—';
                        if (!empty($policy->policy_no) && preg_match('/\(([^\)]+)\)/', $policy->policy_no, $m)) {
                            $policyYear = $m[1];
                        } elseif ($policy->receive_date) {
                            $policyYear = optional($policy->receive_date)->format('Y');
                        }
                    @endphp
                    <tr>
                        <td>{{ $policies->firstItem() + $loop->index }}</td>
                        <td>{{ $policy->doctor_name ?? '—' }}</td>
                        <td>{{ $policy->policy_no ?? '—' }}</td>
                        <td>{{ $policyYear }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-slate-500">No data available in table</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($policies->hasPages())
        <div class="mt-4">{{ $policies->links() }}</div>
    @endif
</section>
@endsection

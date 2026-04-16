@extends('admin.layouts.app')

@section('title', 'Premium Amount')
@section('page-title', 'Account Management')

@section('content')
<section class="section-card">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h3 class="section-title mb-1">Premium Amount ({{ $doctors->total() }})</h3>
            <p class="text-sm text-slate-600">Doctors with coverage, premium, and renewal values used for account management.</p>
        </div>

        <form method="GET" action="{{ route('admin.premium-amount.index') }}" class="flex flex-wrap items-center gap-2">
            <input
                type="search"
                name="search"
                value="{{ $search }}"
                placeholder="Search doctor, receipt, membership, phone"
                class="master-search-input"
            >
            <button type="submit" class="btn btn-primary">Search</button>
            @if(request()->filled('search'))
                <a href="{{ route('admin.premium-amount.index') }}" class="btn btn-default">Clear</a>
            @endif
        </form>
    </div>

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
        <table class="data-table min-w-[1200px]">
            <thead>
                <tr>
                    <th>SL No</th>
                    <th>DR. NAME</th>
                    <th>POLICY NO</th>
                    <th>INSURANCE COVERAGE</th>
                    <th>PREMIUM. AMT</th>
                    <th>GST</th>
                    <th>COMMISSION</th>
                    <th>TOTAL. AMT</th>
                    <th>RENEWAL DATE</th>
                    <th>ACTION</th>
                </tr>
            </thead>
            <tbody>
                @forelse($doctors as $doctor)
                    @php
                        $planCoverage = match ((int) $doctor->plan) {
                            1 => $planCoverageMaps[1]->first(),
                            2 => $planCoverageMaps[2]->first(),
                            3 => $planCoverageMaps[3]->first(),
                            default => null,
                        };
                        $coverageLabel = $doctor->coverage_id ? ($doctor->coverage_id . ' Lakh') : ($planCoverage?->coverage_lakh ? $planCoverage->coverage_lakh . ' Lakh' : '—');
                        $premiumAmount = (float) ($doctor->payment_amount ?? 0);
                        $serviceAmount = (float) ($doctor->service_amount ?? 0);
                        $totalAmount = (float) ($doctor->total_amount ?? ($premiumAmount + $serviceAmount));
                        $gstAmount = $planCoverage?->service_tax_percent ? round(($premiumAmount * (float) $planCoverage->service_tax_percent) / 100, 2) : 0;
                        $commissionAmount = 0;
                        $renewalDate = optional($doctor->created_at)->copy()?->addYear();
                    @endphp
                    <tr>
                        <td><b>{{ $doctors->firstItem() + $loop->index }}</b></td>
                        <td>
                            <b>
                                <a href="{{ route('admin.doctors.show', $doctor->id) }}" target="_blank">{{ $doctor->doctor_name ?? '—' }}</a>
                            </b>
                        </td>
                        <td><b>{{ $doctor->money_rc_no ?? '—' }}</b></td>
                        <td><b>{{ $coverageLabel }}</b></td>
                        <td><b>Rs. {{ number_format($premiumAmount, 0) }}/-</b></td>
                        <td><b>Rs. {{ number_format($gstAmount, 0) }}/-</b></td>
                        <td><b>Rs. {{ number_format($commissionAmount, 0) }}/-</b></td>
                        <td><b>Rs. {{ number_format($totalAmount, 0) }}/-</b></td>
                        <td><b>{{ $renewalDate?->format('d/m/Y') ?? '—' }}</b></td>
                        <td>
                            <div class="flex flex-wrap gap-1">
                                <a href="{{ route('admin.policy-receipt.legacy-create', $doctor->id) }}" class="inline-flex items-center gap-1 rounded bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-700 hover:bg-blue-200" title="Add policy received">
                                    <i class="ri-add-circle-line"></i>
                                </a>
                                <a href="{{ route('admin.policy-receipt.index', ['search' => $doctor->doctor_name]) }}" class="inline-flex items-center gap-1 rounded bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700 hover:bg-emerald-200" title="View policy received">
                                    <i class="ri-eye-line"></i>
                                </a>
                                <a href="{{ route('admin.receipts') }}?search={{ urlencode($doctor->doctor_name ?? '') }}" class="inline-flex items-center gap-1 rounded bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-200" title="Open money receipts">
                                    <i class="ri-bank-card-line"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="py-8 text-center text-slate-500">No premium records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($doctors->hasPages())
        <div class="mt-4">
            {{ $doctors->links() }}
        </div>
    @endif
</section>
@endsection

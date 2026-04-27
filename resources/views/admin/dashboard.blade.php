@extends('admin.layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Membership Intelligence')

@section('content')
@php
    $change = $payments['previous_year'] > 0
        ? (($payments['this_year'] - $payments['previous_year']) / $payments['previous_year']) * 100
        : 0;
    $kpiCards = [
        ['key' => 'enrollment_doctors', 'label' => 'Enrollment Doctors', 'icon' => 'ri-user-heart-line', 'tone' => 'kpi-blue'],
        ['key' => 'money_receipts', 'label' => 'Money Receipts', 'icon' => 'ri-secure-payment-line', 'tone' => 'kpi-green'],
        ['key' => 'doctor_cases', 'label' => 'Doctor Cases', 'icon' => 'ri-briefcase-2-line', 'tone' => 'kpi-red'],
        ['key' => 'lapse_list', 'label' => 'Lapse List', 'icon' => 'ri-timer-flash-line', 'tone' => 'kpi-amber'],
        ['key' => 'premium_amount', 'label' => 'Premium Amount', 'icon' => 'ri-vip-crown-2-line', 'tone' => 'kpi-cyan'],
        ['key' => 'doctor_posts', 'label' => 'Doctor Posts', 'icon' => 'ri-news-line', 'tone' => 'kpi-violet'],
    ];
@endphp

<div class="mb-6 grid gap-4 lg:grid-cols-[1.8fr_1fr]">
    <section class="section-card">
        <p class="mb-1 text-xs font-semibold uppercase tracking-[0.14em] text-blue-600">Doctor Enrollment & Membership</p>
        <h2 class="page-title text-2xl font-bold leading-tight text-slate-900 md:text-3xl">Performance Snapshot</h2>
        <p class="mt-2 max-w-2xl text-sm text-slate-600">Track enrollments, renewals, lapses, and payment momentum across the network with a real-time operational view.</p>
    </section>
    <section class="section-card bg-gradient-to-br from-blue-600 to-cyan-500 text-white">
        <p class="text-xs uppercase tracking-[0.14em] text-blue-100">All Time Payment</p>
        <p class="widget-value mt-2 text-3xl font-bold">₹{{ number_format($payments['all_time']) }}</p>
        <div class="mt-3 flex items-center gap-2 text-sm">
            <i class="{{ $change >= 0 ? 'ri-arrow-up-circle-line' : 'ri-arrow-down-circle-line' }}"></i>
            <span>{{ number_format(abs($change), 1) }}% {{ $change >= 0 ? 'increase' : 'decrease' }} vs previous year</span>
        </div>
    </section>
</div>

<section class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
    @foreach($kpiCards as $card)
        <article class="kpi-card {{ $card['tone'] }}">
            <div class="mb-4 flex items-center justify-between">
                <p class="text-sm font-semibold text-slate-600">{{ $card['label'] }}</p>
                <div class="kpi-icon"><i class="{{ $card['icon'] }}"></i></div>
            </div>
            <p class="widget-value text-3xl font-bold text-slate-900">{{ number_format($stats[$card['key']]) }}</p>
        </article>
    @endforeach
</section>

<div class="mb-6 grid gap-6 xl:grid-cols-2">
    <section class="section-card">
        <h3 class="section-title">Last 6 Month Report</h3>
        <div class="metric-row"><span class="font-semibold text-slate-700">Enrollment</span><strong class="text-blue-700">{{ number_format($lastSixMonthsEnrollment) }}</strong></div>
        <div class="metric-row"><span class="font-semibold text-slate-700">Renew</span><strong class="text-emerald-700">{{ number_format($lastSixMonthsRenew) }}</strong></div>
        <div class="metric-row"><span class="font-semibold text-slate-700">Lapse</span><strong class="text-rose-700">{{ number_format($lastSixMonthsLapse) }}</strong></div>
    </section>

    <section class="section-card">
        <h3 class="section-title">Enrollment Comparison</h3>
        <div class="metric-row"><span>Previous Year Enrollment</span><strong>{{ number_format($yearComparison['previous_enrollment']) }}</strong></div>
        <div class="metric-row"><span>This Year Enrollment</span><strong>{{ number_format($yearComparison['current_enrollment']) }}</strong></div>
        <div class="metric-row"><span>Previous Year Renew</span><strong>{{ number_format($yearComparison['previous_renew']) }}</strong></div>
        <div class="metric-row"><span>This Year Renew</span><strong>{{ number_format($yearComparison['current_renew']) }}</strong></div>
    </section>
</div>

<div class="mb-6 grid gap-6 xl:grid-cols-2">
    <section class="section-card">
        <h3 class="section-title">Doctors Progress Report</h3>
        @php
            $progressRows = [
                ['label' => 'Doctors having documents', 'count' => $progress['with_documents']['count'], 'total' => $progress['with_documents']['total']],
                ['label' => 'Doctors having case', 'count' => $progress['with_cases']['count'], 'total' => $progress['with_cases']['total']],
                ['label' => 'Doctors having premium plan', 'count' => $progress['with_premium']['count'], 'total' => $progress['with_premium']['total']],
                ['label' => 'Doctors having photo', 'count' => $progress['with_photo']['count'], 'total' => $progress['with_photo']['total']],
                ['label' => 'Doctors renew date over', 'count' => $progress['renew_expired']['count'], 'total' => $progress['renew_expired']['total']],
            ];
        @endphp
        <div class="space-y-4">
            @foreach($progressRows as $row)
                @php $pct = $row['total'] > 0 ? ($row['count'] / $row['total']) * 100 : 0; @endphp
                <div>
                    <div class="mb-2 flex items-center justify-between text-sm">
                        <span class="font-semibold text-slate-700">{{ $row['label'] }}</span>
                        <span class="text-slate-500">{{ number_format($row['count']) }} / {{ number_format($row['total']) }}</span>
                    </div>
                    <div class="progress-track"><div class="progress-bar" style="width: {{ $pct }}%"></div></div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="space-y-6">
        <article class="section-card">
            <h3 class="section-title">Doctor Plan Report</h3>
            <div class="space-y-3">
                <div class="metric-row"><span>Doctors with normal plan</span><strong>{{ number_format($plans['normal']) }}</strong></div>
                <div class="metric-row"><span>Doctors with high plan</span><strong>{{ number_format($plans['high']) }}</strong></div>
                <div class="metric-row"><span>Doctors with combo plan</span><strong>{{ number_format($plans['combo']) }}</strong></div>
            </div>
        </article>

        <article class="section-card">
            <h3 class="section-title">Payment Reports</h3>
            <div class="space-y-3">
                <div class="metric-row"><span>Total payment this year</span><strong>₹{{ number_format($payments['this_year']) }}</strong></div>
                <div class="metric-row"><span>Total payment previous year</span><strong>₹{{ number_format($payments['previous_year']) }}</strong></div>
                <div class="metric-row"><span>Total payment all time</span><strong>₹{{ number_format($payments['all_time']) }}</strong></div>
            </div>
        </article>
    </section>
</div>

<section class="section-card">
    <div class="mb-4 flex items-center justify-between">
        <h3 class="section-title mb-0">Latest Enrolled Doctors</h3>
        <a href="{{ route('admin.enrollment') }}" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">View All</a>
    </div>

    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Doctor</th>
                    <th>Enrollment Date</th>
                    <th class="text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($latest_doctors as $doctor)
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                @php
                                    $displayName = trim((string) $doctor->doctor_name);
                                    $initial = strtoupper(substr(preg_replace('/^DR\.\s*/i', '', $displayName), 0, 1));
                                @endphp
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100 font-bold text-blue-700">{{ $initial !== '' ? $initial : 'D' }}</div>
                                <p class="font-semibold text-slate-800">{{ $displayName !== '' ? $displayName : 'N/A' }}</p>
                            </div>
                        </td>
                        <td class="font-medium text-slate-600">{{ optional($doctor->created_at)->format('d/m/Y') ?? 'N/A' }}</td>
                        <td class="text-right">
                            <a href="{{ route('admin.doctors.show', $doctor->id) }}" class="rounded-lg bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-100">Open Profile</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center text-slate-500">No enrolled doctors found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection

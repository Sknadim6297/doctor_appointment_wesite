{{-- Employee enrollment workspace (included from dashboard when user is employee-like) --}}
<section class="mb-6 rounded-2xl border border-blue-200 bg-gradient-to-br from-blue-50 to-white p-6 shadow-sm">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-wide text-blue-700">My enrollment workspace</p>
            <h2 class="mt-1 text-2xl font-bold text-slate-900">Track your submissions</h2>
            <p class="mt-1 text-sm text-slate-600">Drafts autosave as you work. After Step 1 submit, an admin must approve before Steps 2–4 unlock.</p>
        </div>
        <a href="{{ route('admin.enrollment.create') }}" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-500">New enrollment</a>
    </div>
    <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        @foreach([
            ['k' => 'draft', 'label' => 'Drafts', 'tone' => 'text-slate-700'],
            ['k' => 'pending', 'label' => 'Pending approval', 'tone' => 'text-amber-700'],
            ['k' => 'approved', 'label' => 'Approved', 'tone' => 'text-emerald-700'],
            ['k' => 'rejected', 'label' => 'Rejected', 'tone' => 'text-rose-700'],
            ['k' => 'incomplete', 'label' => 'In progress', 'tone' => 'text-blue-700'],
        ] as $card)
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-semibold uppercase text-slate-500">{{ $card['label'] }}</p>
                <p class="mt-2 text-2xl font-bold {{ $card['tone'] }}">{{ number_format($employeeDashboardStats[$card['k']] ?? 0) }}</p>
            </div>
        @endforeach
    </div>
</section>


@extends('admin.layouts.app')

@section('title', 'Call Sheet')
@section('page-title', 'Marketing Call Sheet')

@section('content')
<section class="section-card">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <h3 class="section-title mb-0">Call sheet ({{ $callSheets->total() }})</h3>

        <div class="flex flex-wrap items-center gap-2">
            <form method="GET" action="{{ route('admin.call-sheet.index') }}" class="flex flex-wrap items-center gap-2">
                <select name="search_month" class="master-search-input">
                    <option value="">---Select Month---</option>
                    @foreach($months as $month)
                        <option value="{{ $month }}" {{ $selectedMonth === $month ? 'selected' : '' }}>{{ $month }}</option>
                    @endforeach
                </select>

                <select name="search_year" class="master-search-input">
                    <option value="">---Select Year---</option>
                    @foreach($years as $year)
                        <option value="{{ $year }}" {{ (string) $selectedYear === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                    @endforeach
                </select>

                <button type="submit" class="btn btn-primary">Search</button>
                @if(!empty($selectedMonth) || !empty($selectedYear))
                    <a href="{{ route('admin.call-sheet.index') }}" class="btn btn-default">Clear</a>
                @endif
            </form>

            <button type="button" class="btn btn-default" onclick="printCallSheet()" title="Print All">
                <i class="ri-printer-line"></i>
                <span>Print</span>
            </button>

            <a class="btn btn-primary" href="{{ route('admin.call-sheet.csv', request()->query()) }}" title="Export CSV">
                <i class="ri-file-excel-2-line"></i>
                <span>Export CSV</span>
            </a>
        </div>
    </div>

    <div id="print_content" class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>SL No.</th>
                    <th>Name</th>
                    <th>Specialization</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($callSheets as $item)
                    <tr>
                        <td>{{ $callSheets->firstItem() + $loop->index }}</td>
                        <td>{{ $item->doctor_name ?: 'N/A' }}</td>
                        <td>{{ $item->specialization?->name ?: 'N/A' }}</td>
                        <td>{{ $item->doctor_email ?: 'N/A' }}</td>
                        <td>{{ $item->mobile1 ?: 'N/A' }}</td>
                        <td>
                            <a href="{{ route('admin.doctors.show', $item->id) }}" class="inline-flex items-center gap-1 rounded-lg bg-sky-100 px-3 py-2 text-xs font-semibold text-sky-700 hover:bg-sky-200">
                                <i class="ri-eye-line"></i>
                                <span>View</span>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-slate-500">No data available in table</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($callSheets->hasPages())
        <div class="mt-4">{{ $callSheets->links() }}</div>
    @endif
</section>

@push('scripts')
<script>
    function printCallSheet() {
        const content = document.getElementById('print_content');
        if (!content) return;

        const printWindow = window.open('', '_blank');
        if (!printWindow) return;

        printWindow.document.write(`
            <html>
                <head>
                    <title>Call Sheet</title>
                    <style>
                        body { font-family: Arial, sans-serif; padding: 20px; }
                        table { width: 100%; border-collapse: collapse; }
                        th, td { border: 1px solid #d1d5db; padding: 8px; font-size: 12px; text-align: left; }
                        th { background: #f1f5f9; }
                    </style>
                </head>
                <body>
                    <h3>Call sheet</h3>
                    ${content.innerHTML}
                </body>
            </html>
        `);

        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
        printWindow.close();
    }
</script>
@endpush
@endsection

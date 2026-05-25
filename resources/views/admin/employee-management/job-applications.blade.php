@extends('admin.layouts.app')

@section('title', 'Job Applications')
@section('page-title', 'Employee Management')

@section('content')
<section class="section-card">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <h3 class="section-title mb-0">Job applications ({{ $applications->total() }})</h3>
        <form method="GET" action="{{ route('admin.job-applications.index') }}" class="flex flex-wrap items-center gap-2">
            <input type="search" name="search" class="master-search-input" placeholder="Search name, email, mobile" value="{{ $search }}">
            <button type="submit" class="btn btn-primary">Search</button>
            @if($search !== '')
                <a href="{{ route('admin.job-applications.index') }}" class="btn btn-default">Clear</a>
            @endif
        </form>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif

    <div class="overflow-x-auto">
        <table class="data-table w-full">
            <thead>
                <tr>
                    <th>SL</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Mobile</th>
                    <th>Salary</th>
                    <th>Document</th>
                    <th>Applied on</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($applications as $application)
                    <tr>
                        <td>{{ $applications->firstItem() + $loop->index }}</td>
                        <td>{{ $application->name }}</td>
                        <td>{{ $application->email ?? '—' }}</td>
                        <td>{{ $application->mobile ?? '—' }}</td>
                        <td>Rs {{ number_format((float) $application->salary, 0) }}</td>
                        <td>{{ $application->document ?? '—' }}</td>
                        <td>{{ optional($application->applied_at)->format('d M Y H:i') ?? '—' }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.job-applications.destroy', $application) }}" onsubmit="return confirm('Delete this application?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex items-center gap-1 rounded-lg bg-rose-100 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-200">
                                    <i class="ri-delete-bin-line"></i> Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-8 text-slate-500">
                            No job applications found. Import legacy data with
                            <code class="text-xs bg-slate-100 px-1 rounded">php artisan legacy:import-jobs --replace</code>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $applications->links() }}</div>
</section>
@endsection

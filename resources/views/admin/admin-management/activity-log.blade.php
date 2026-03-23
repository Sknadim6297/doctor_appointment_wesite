@extends('admin.layouts.app')

@section('title', 'Activity Log')
@section('page-title', 'Activity Log')

@section('content')
<section class="section-card">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <h3 class="section-title mb-0">Activity Log - {{ strtoupper($admin->name) }}</h3>
        <a href="{{ route('admin.admin-management.index') }}" class="btn btn-default">Back To Sub-Admin List</a>
    </div>

    <div class="overflow-x-auto">
        <table class="data-table min-w-[1200px]">
            <thead>
                <tr>
                    <th>SL No.</th>
                    <th>Time</th>
                    <th>Actor</th>
                    <th>Owner</th>
                    <th>Module</th>
                    <th>Action</th>
                    <th>Description</th>
                    <th>Device</th>
                    <th>Browser</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $index => $log)
                    <tr>
                        <td>{{ $logs->firstItem() + $index }}</td>
                        <td>{{ optional($log->occurred_at)->format('d/m/Y h:i A') }}</td>
                        <td>{{ $log->actor?->name ?: '-' }}</td>
                        <td>{{ $log->owner?->name ?: '-' }}</td>
                        <td>{{ str($log->module_key)->replace('_', ' ')->title() }}</td>
                        <td>{{ str($log->action)->replace('_', ' ')->title() }}</td>
                        <td>{{ $log->description ?: '-' }}</td>
                        <td>{{ trim(($log->device_name ?: '-') . ' / ' . ($log->device_type ?: '-')) }}</td>
                        <td>{{ $log->browser_name ?: '-' }}</td>
                        <td>{{ $log->ip_address ?: '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="py-8 text-center text-slate-500">No activity logs found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
        <div class="mt-4 border-t border-slate-200 pt-4">
            {{ $logs->links() }}
        </div>
    @endif
</section>
@endsection
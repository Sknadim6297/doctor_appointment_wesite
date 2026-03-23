@extends('admin.layouts.app')

@section('title', 'Login Log')
@section('page-title', 'Login Log')

@section('content')
<section class="section-card">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <h3 class="section-title mb-0">Login Log - {{ strtoupper($admin->name) }}</h3>
        <a href="{{ route('admin.admin-management.index') }}" class="btn btn-default">Back To Sub-Admin List</a>
    </div>

    <div class="overflow-x-auto">
        <table class="data-table min-w-[920px]">
            <thead>
                <tr>
                    <th>SL No.</th>
                    <th>Login Time</th>
                    <th>Logout Time</th>
                    <th>IP Address</th>
                    <th>Device</th>
                    <th>Browser</th>
                    <th>Raw Agent</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $index => $log)
                    <tr>
                        <td>{{ $logs->firstItem() + $index }}</td>
                        <td>{{ optional($log->logged_in_at)->format('d/m/Y h:i A') }}</td>
                        <td>{{ optional($log->logged_out_at)->format('d/m/Y h:i A') ?: '-' }}</td>
                        <td>{{ $log->ip_address ?: '-' }}</td>
                        <td>{{ trim(($log->device_name ?: '-') . ' / ' . ($log->device_type ?: '-')) }}</td>
                        <td>{{ trim(($log->browser_name ?: '-') . ' ' . ($log->browser_version ?: '')) }}</td>
                        <td class="max-w-[420px] truncate" title="{{ $log->user_agent }}">{{ $log->user_agent ?: '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-500">No login logs found.</td>
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

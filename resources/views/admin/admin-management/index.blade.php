@extends('admin.layouts.app')

@section('title', 'Sub-Admin Management')
@section('page-title', 'Sub-Admin Management')

@section('content')
<section class="section-card">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <h3 class="section-title mb-0">All Sub Admins ({{ $totalAdmins }})</h3>
        <a href="{{ route('admin.admin-management.create') }}" class="btn-brand !px-4 !py-2 text-sm">
            <i class="ri-user-add-line"></i>
            <span>Add Sub-Admin</span>
        </a>
    </div>

    <form method="GET" action="{{ route('admin.admin-management.index') }}" class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-4">
        <input
            type="text"
            name="search"
            value="{{ $search ?? '' }}"
            placeholder="Search name, email, employee no, phone"
            class="master-search-input md:col-span-2"
        >

        <select name="role" class="master-search-input">
            <option value="">All Roles</option>
            @foreach($roleOptions as $role)
                <option value="{{ $role->role_key }}" {{ ($selectedRole ?? '') === $role->role_key ? 'selected' : '' }}>{{ $role->role_title }}</option>
            @endforeach
        </select>

        <select name="status" class="master-search-input">
            <option value="">All Status</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>

        <div class="md:col-span-4 flex flex-wrap items-center gap-2">
            <button type="submit" class="btn btn-primary">Apply Filter</button>
            <a href="{{ route('admin.admin-management.index') }}" class="btn btn-default">Reset</a>
        </div>
    </form>

    <div class="overflow-x-auto">
        <table class="data-table min-w-[920px]">
            <thead>
                <tr>
                    <th>SL No.</th>
                    <th>Profile</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Roles</th>
                    <th>Contact</th>
                    <th>Status</th>
                    <th>User Privileges</th>
                    <th>Logs</th>
                    <th>Created On</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($admins as $index => $admin)
                <tr>
                    <td>{{ $admins->firstItem() + $index }}</td>
                    <td>
                        @if($admin->profile_pic)
                            <img src="{{ asset('storage/' . $admin->profile_pic) }}" alt="{{ $admin->name }}" class="h-10 w-10 rounded-full border border-slate-200 object-cover">
                        @else
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-xs font-semibold text-slate-700">
                                {{ strtoupper(substr($admin->name, 0, 1)) }}
                            </div>
                        @endif
                    </td>
                    <td>{{ strtoupper(trim(($admin->first_name ?? '') . ' ' . ($admin->last_name ?? ''))) ?: strtoupper($admin->name) }}</td>
                    <td>{{ $admin->email }}</td>
                    <td>
                        @php($adminRoleLabels = collect($admin->roles)->pluck('role_title')->all())
                        @if(empty($adminRoleLabels) && !empty($admin->role))
                            @php($adminRoleLabels = [$roleMap[$admin->role] ?? ucwords(str_replace('_', ' ', $admin->role))])
                        @endif
                        <div class="flex flex-wrap gap-1">
                            @forelse($adminRoleLabels as $label)
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">{{ $label }}</span>
                            @empty
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-500">Unassigned</span>
                            @endforelse
                        </div>
                    </td>
                    <td>{{ $admin->phone ?: '-' }}</td>
                    <td>
                        @if($admin->is_active)
                            <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-1 text-xs font-semibold text-green-700">Active</span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-1 text-xs font-semibold text-red-700">Inactive</span>
                        @endif
                    </td>
                    <td>
                        @if($admin->hasAdminRole('super_admin'))
                            <span class="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700" title="Super admin has full access">
                                <i class="ri-lock-unlock-line"></i>
                                <span>All Access</span>
                            </span>
                        @else
                            <a href="{{ route('admin.admin-management.privileges', $admin) }}" class="inline-flex items-center gap-1 rounded-lg bg-blue-100 px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-200" title="User privileges">
                                <i class="ri-shield-user-line"></i>
                                <span>Manage ({{ (int) ($admin->allowed_privileges_count ?? 0) }})</span>
                            </a>
                        @endif
                    </td>
                    <td>
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('admin.admin-management.login-log', $admin) }}" class="inline-flex items-center gap-1 rounded-lg bg-amber-100 px-3 py-2 text-xs font-semibold text-amber-700 hover:bg-amber-200" title="Login log">
                                <i class="ri-history-line"></i>
                                <span>Login ({{ (int) ($admin->login_logs_count ?? 0) }})</span>
                            </a>
                            <a href="{{ route('admin.admin-management.activity-log', $admin) }}" class="inline-flex items-center gap-1 rounded-lg bg-violet-100 px-3 py-2 text-xs font-semibold text-violet-700 hover:bg-violet-200" title="Activity log">
                                <i class="ri-file-list-3-line"></i>
                                <span>Activity ({{ (int) ($admin->activity_logs_count ?? 0) }})</span>
                            </a>
                        </div>
                    </td>
                    <td>{{ $admin->created_at->format('d/m/Y') }}</td>
                    <td>
                        @php($isProtectedAdmin = $admin->hasAdminRole('super_admin'))
                        <div class="flex items-center gap-2">
                            @if($isProtectedAdmin)
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-600">Protected</span>
                            @else
                                <a href="{{ route('admin.admin-management.edit', $admin) }}" class="inline-flex items-center gap-1 rounded-lg bg-emerald-100 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-200" title="Edit sub admin">
                                    <i class="ri-pencil-line"></i>
                                    <span>Edit</span>
                                </a>
                                @if($admin->id !== auth()->id())
                                <form method="POST" action="{{ route('admin.admin-management.destroy', $admin) }}" onsubmit="return confirm('Are you sure you want to delete this admin?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center gap-1 rounded-lg bg-rose-100 px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-200" title="Delete">
                                        <i class="ri-delete-bin-line"></i>
                                        <span>Delete</span>
                                    </button>
                                </form>
                                @endif
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="11" class="py-8 text-center text-slate-500">No sub admins found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($admins->hasPages())
    <div class="mt-4 border-t border-slate-200 pt-4">
        {{ $admins->links() }}
    </div>
    @endif
</section>
@endsection

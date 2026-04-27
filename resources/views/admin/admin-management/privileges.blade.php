@extends('admin.layouts.app')

@section('title', 'User Privileges')
@section('page-title', 'User Privileges')

@section('content')
<section class="section-card">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <h3 class="section-title mb-0">Sidebar Permissions ({{ $totalPrivileges }}) - {{ strtoupper($admin->name) }}</h3>
        <a href="{{ route('admin.admin-management.index') }}" class="btn btn-default">Back To Sub-Admin List</a>
    </div>

    <form method="POST" action="{{ route('admin.admin-management.privileges.update', $admin) }}" id="privilegeForm">
        @csrf

        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                <input type="checkbox" id="parent_check_id" class="rounded border-slate-300">
                <span>Select all from this page</span>
            </label>

            <div class="flex items-center gap-2">
                <button type="submit" name="action" value="allow" class="btn btn-primary">Allow</button>
                <button type="submit" name="action" value="disallow" class="btn btn-default">Dis-Allow</button>
            </div>
        </div>

        @error('sidebar_keys')
            <p class="mb-3 text-sm text-red-600">{{ $message }}</p>
        @enderror

        <p class="mb-3 text-xs text-slate-500">Select one or more sidebar nodes, then click Allow or Dis-Allow to update access.</p>

        <div class="space-y-4">
            @foreach($groupedPrivileges as $node)
                @include('admin.admin-management._sidebar-permission-tree', ['node' => $node, 'selectedKeys' => $selectedPrivilegeKeys, 'level' => 0])
            @endforeach
        </div>
    </form>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const parentCheckbox = document.getElementById('parent_check_id');
        const children = document.querySelectorAll('.sidebar-node-checkbox');

        if (!parentCheckbox || !children.length) return;

        const syncParentState = () => {
            const checkedCount = Array.from(children).filter((checkbox) => checkbox.checked).length;
            parentCheckbox.checked = checkedCount === children.length;
            parentCheckbox.indeterminate = checkedCount > 0 && checkedCount < children.length;
        };

        parentCheckbox.addEventListener('change', function () {
            children.forEach((checkbox) => {
                checkbox.checked = parentCheckbox.checked;
            });

            syncParentState();
        });

        children.forEach((checkbox) => {
            checkbox.addEventListener('change', syncParentState);
        });

        syncParentState();
    });

    document.addEventListener('change', function (event) {
        var checkbox = event.target.closest('.sidebar-node-checkbox');
        if (!checkbox) return;

        var node = checkbox.closest('[data-sidebar-node]');
        if (!node) return;

        node.querySelectorAll('input.sidebar-node-checkbox').forEach(function (childCheckbox) {
            if (childCheckbox === checkbox) return;
            childCheckbox.checked = checkbox.checked;
        });
    });
</script>
@endsection

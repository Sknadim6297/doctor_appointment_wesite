@php
    $selectedKeys = $selectedKeys ?? [];
    $level = $level ?? 0;
    $nodeId = 'sidebar-node-' . $node['key'];
    $hasChildren = !empty($node['children']);
    $permissionKey = $node['permission_key'] ?? ('sidebar.' . $node['key']);
@endphp

<div class="rounded-xl border border-slate-200 bg-white" data-sidebar-node="{{ $node['key'] }}" style="margin-left: {{ $level * 1.25 }}rem;">
    <div class="flex items-start gap-3 px-4 py-3 {{ $hasChildren ? 'border-b border-slate-200' : '' }}">
        <input
            type="checkbox"
            id="{{ $nodeId }}"
            name="sidebar_keys[]"
            value="{{ $permissionKey }}"
            class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 sidebar-node-checkbox"
            {{ in_array($permissionKey, $selectedKeys, true) ? 'checked' : '' }}
            data-sidebar-node-checkbox="{{ $permissionKey }}"
        >
        <label for="{{ $nodeId }}" class="flex-1 cursor-pointer">
            <div class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                @if(!empty($node['icon']))
                    <i class="{{ $node['icon'] }} text-slate-500"></i>
                @endif
                <span>{{ $node['title'] }}</span>
            </div>
            <p class="mt-1 text-xs text-slate-500">{{ $hasChildren ? 'Select this menu to grant access to its submenu items.' : 'Select this item to show it in the sidebar.' }}</p>
        </label>
    </div>

    @if($hasChildren)
        <div class="space-y-3 px-4 py-4">
            @foreach($node['children'] as $child)
                @include('admin.admin-management._sidebar-permission-tree', ['node' => $child, 'selectedKeys' => $selectedKeys, 'level' => $level + 1])
            @endforeach
        </div>
    @endif
</div>

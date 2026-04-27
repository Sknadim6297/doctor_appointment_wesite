@php
    $routeNames = (array) ($node['route_names'] ?? []);
    $hasChildren = !empty($node['children']);
    $isActive = !empty($routeNames) && request()->routeIs(...$routeNames);
@endphp

@if($hasChildren)
    <div class="treeview" x-data="{ open: {{ $isActive ? 'true' : 'false' }} }">
        <button type="button" class="tree-toggle nav-link w-full {{ $isActive ? 'active' : '' }}" @click="open = !open">
            <span class="flex items-center gap-3">
                @if(!empty($node['icon']))
                    <i class="{{ $node['icon'] }}"></i>
                @endif
                <span>{{ $node['title'] }}</span>
            </span>
            <i class="ri-arrow-right-s-line tree-arrow" :class="{ 'rotate-90': open }"></i>
        </button>
        <ul class="tree-menu" x-show="open" x-transition.opacity x-cloak>
            @foreach($node['children'] as $child)
                @include('admin.layouts.sidebar-node', ['node' => $child])
            @endforeach
        </ul>
    </div>
@else
    <a href="{{ route($node['route']) }}" class="nav-link {{ $isActive ? 'active' : '' }}">
        @if(!empty($node['icon']))
            <i class="{{ $node['icon'] }}"></i>
        @endif
        <span>{{ $node['title'] }}</span>
    </a>
@endif

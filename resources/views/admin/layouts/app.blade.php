<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') - MediForum Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/css/admin-theme.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="admin-shell" x-data="{ sidebarOpen: false }">
    <div class="flex min-h-screen overflow-hidden">

         <aside class="side-rail fixed inset-y-0 left-0 z-50 w-72 flex-shrink-0 transform p-5 transition-transform duration-300 lg:static lg:translate-x-0"
             :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
             x-data="{
                 mdmOpen: @json(request()->routeIs('admin.specialization') || request()->routeIs('admin.plans') || request()->routeIs('admin.high-risk-plans') || request()->routeIs('admin.combo-plans') || request()->routeIs('admin.insurance-plans')),
                 empOpen: @json(request()->routeIs('admin.admin-management.*')),
                 doctorOpen: @json(request()->routeIs('admin.enrollment') || request()->routeIs('admin.doctors')),
                 toggleMenu(menu) {
                    if (menu === 'mdm') this.mdmOpen = !this.mdmOpen;
                    if (menu === 'emp') this.empOpen = !this.empOpen;
                    if (menu === 'doctor') this.doctorOpen = !this.doctorOpen;
                 }
             }">
            <div class="mb-6 flex items-center justify-between rounded-2xl bg-white/10 px-4 py-3">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-xl text-blue-700">
                        <i class="ri-verified-badge-line"></i>
                    </div>
                    <div>
                        <p class="brand-title text-lg font-bold text-white">MediForum</p>
                        <p class="text-xs text-blue-100">Admin Console</p>
                    </div>
                </div>
                <button class="text-white lg:hidden" @click="sidebarOpen = false"><i class="ri-close-line text-xl"></i></button>
            </div>

            <nav class="sidebar-menu space-y-2 overflow-y-auto pb-8">
                <p class="menu-section-title">ADMINISTRATION</p>

                <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="ri-dashboard-line"></i>
                    <span>Dashboard</span>
                </a>

                <div class="treeview">
                    <button type="button" class="tree-toggle nav-link w-full {{ request()->routeIs('admin.specialization') || request()->routeIs('admin.plans') || request()->routeIs('admin.high-risk-plans') || request()->routeIs('admin.combo-plans') || request()->routeIs('admin.insurance-plans') ? 'active' : '' }}" @click="toggleMenu('mdm')">
                        <span class="flex items-center gap-3">
                            <i class="ri-layout-grid-line"></i>
                            <span>Master Data Management</span>
                        </span>
                        <i class="ri-arrow-right-s-line tree-arrow" :class="{ 'rotate-90': mdmOpen }"></i>
                    </button>
                    <ul class="tree-menu" x-show="mdmOpen" x-transition.opacity x-cloak>
                        <li><a href="{{ route('admin.specialization') }}" class="{{ request()->routeIs('admin.specialization') ? 'active' : '' }}"><span class="submenu-left"><i class="ri-list-check-2"></i><span>Specialization</span></span></a></li>
                        <li><a href="{{ route('admin.plans') }}" class="{{ request()->routeIs('admin.plans') ? 'active' : '' }}"><span class="submenu-left"><i class="ri-list-check-2"></i><span>Normal Plan</span></span></a></li>
                        <li><a href="{{ route('admin.high-risk-plans') }}" class="{{ request()->routeIs('admin.high-risk-plans') ? 'active' : '' }}"><span class="submenu-left"><i class="ri-list-check-2"></i><span>High Risk Plan</span></span></a></li>
                        <li><a href="{{ route('admin.combo-plans') }}" class="{{ request()->routeIs('admin.combo-plans') ? 'active' : '' }}"><span class="submenu-left"><i class="ri-list-check-2"></i><span>Combo Plan</span></span></a></li>
                        <li><a href="{{ route('admin.insurance-plans') }}" class="{{ request()->routeIs('admin.insurance-plans') ? 'active' : '' }}"><span class="submenu-left"><i class="ri-list-check-2"></i><span>Insurence Plan</span></span></a></li>
                    </ul>
                </div>

                <div class="treeview">
                    <button type="button" class="tree-toggle nav-link w-full {{ request()->routeIs('admin.admin-management.*') ? 'active' : '' }}" @click="toggleMenu('emp')">
                        <span class="flex items-center gap-3">
                            <i class="ri-user-settings-line"></i>
                            <span>Employee Management</span>
                        </span>
                        <i class="ri-arrow-right-s-line tree-arrow" :class="{ 'rotate-90': empOpen }"></i>
                    </button>
                    <ul class="tree-menu" x-show="empOpen" x-transition.opacity x-cloak>
                        @if(auth()->user()->role === 'super_admin')
                            <li>
                                <a href="{{ route('admin.admin-management.index') }}" class="has-badge {{ request()->routeIs('admin.admin-management.*') ? 'active' : '' }}">
                                    <span class="submenu-left"><i class="ri-list-check-2"></i><span>Sub Admin Management</span></span>
                                    <small class="menu-badge">2</small>
                                </a>
                            </li>
                            <li><a href="{{ route('admin.admin-management.index') }}" class="{{ request()->routeIs('admin.admin-management.*') ? 'active' : '' }}"><span class="submenu-left"><i class="ri-list-check-2"></i><span>Role Management</span></span></a></li>
                        @else
                            <li><a href="#"><span class="submenu-left"><i class="ri-list-check-2"></i><span>Sub Admin Management</span></span></a></li>
                            <li><a href="#"><span class="submenu-left"><i class="ri-list-check-2"></i><span>Role Management</span></span></a></li>
                        @endif
                    </ul>
                </div>

                <div class="treeview">
                    <button type="button" class="tree-toggle nav-link w-full {{ request()->routeIs('admin.enrollment') || request()->routeIs('admin.doctors') ? 'active' : '' }}" @click="toggleMenu('doctor')">
                        <span class="flex items-center gap-3">
                            <i class="ri-refresh-line"></i>
                            <span>Doctor Management</span>
                        </span>
                        <i class="ri-arrow-right-s-line tree-arrow" :class="{ 'rotate-90': doctorOpen }"></i>
                    </button>
                    <ul class="tree-menu" x-show="doctorOpen" x-transition.opacity x-cloak>
                        <li><a href="{{ route('admin.enrollment') }}" class="{{ request()->routeIs('admin.enrollment') ? 'active' : '' }}"><span class="submenu-left"><i class="ri-list-check-2"></i><span>Enrollment Entry</span></span></a></li>
                        <li>
                            <a href="{{ route('admin.doctors') }}" class="has-badge {{ request()->routeIs('admin.doctors') ? 'active' : '' }}">
                                <span class="submenu-left"><i class="ri-list-check-2"></i><span>Doctor List</span></span>
                                <small class="menu-badge bg-red-500">954</small>
                            </a>
                        </li>
                        <li><a href="{{ route('admin.doctors') }}" class="{{ request()->routeIs('admin.doctors') ? 'active' : '' }}"><span class="submenu-left"><i class="ri-list-check-2"></i><span>Membership nos.</span></span></a></li>
                    </ul>
                </div>

                <a href="{{ route('admin.reports') }}" class="nav-link {{ request()->routeIs('admin.reports') ? 'active' : '' }}">
                    <i class="ri-bar-chart-2-line"></i>
                    <span>Reports</span>
                </a>

                <form method="POST" action="{{ route('admin.logout') }}" class="pt-2">
                    @csrf
                    <button type="submit" class="nav-link logout w-full">
                        <i class="ri-logout-box-r-line"></i>
                        <span>Logout</span>
                    </button>
                </form>
            </nav>
        </aside>

        <div x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-40 bg-slate-900/50 lg:hidden" @click="sidebarOpen = false" style="display: none;"></div>

        <div class="flex min-h-screen flex-1 flex-col min-w-0 overflow-hidden">
            <header class="topbar sticky top-0 z-30 flex h-16 items-center justify-between px-4 md:px-8">
                <div class="flex items-center gap-3">
                    <button class="rounded-xl border border-slate-200 bg-white p-2 text-slate-700 lg:hidden" @click="sidebarOpen = true">
                        <i class="ri-menu-2-line"></i>
                    </button>
                    <h1 class="page-title text-xl font-bold text-slate-900">@yield('page-title', 'Dashboard')</h1>
                </div>

                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="glass-panel flex items-center gap-3 rounded-xl px-3 py-2">
                        <div class="hidden text-right sm:block">
                            <p class="text-sm font-semibold text-slate-800">{{ auth()->user()->name }}</p>
                            <p class="text-xs capitalize text-slate-500">{{ str_replace('_', ' ', auth()->user()->role) }}</p>
                        </div>
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600 text-sm font-bold text-white">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <i class="ri-arrow-down-s-line text-slate-600"></i>
                    </button>

                    <div x-show="open" @click.away="open = false" x-transition
                         class="absolute right-0 mt-2 w-56 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl" style="display: none;">
                        <div class="mb-2 rounded-xl bg-slate-50 p-3">
                            <p class="text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-slate-500">{{ auth()->user()->email }}</p>
                        </div>
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold text-red-600 hover:bg-red-50">
                                <i class="ri-logout-box-r-line"></i>
                                <span>Logout</span>
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <main class="flex-1 px-4 py-6 md:px-8">
                @if(session('success'))
                    <div class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>

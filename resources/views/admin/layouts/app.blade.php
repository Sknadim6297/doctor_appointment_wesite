<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') - MediForum Admin</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('assets/images/mediforum-logo.jpeg') }}">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/css/admin-theme.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="admin-shell" x-data="{ sidebarOpen: false }">
    <div class="flex min-h-screen overflow-hidden">

         <aside class="side-rail fixed inset-y-0 left-0 z-50 flex h-screen max-h-screen w-72 flex-shrink-0 flex-col overflow-hidden transform p-5 transition-transform duration-300 lg:static lg:translate-x-0"
             :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
             x-data="{
                 mdmOpen: @json(request()->routeIs('admin.specialization') || request()->routeIs('admin.plans') || request()->routeIs('admin.high-risk-plans') || request()->routeIs('admin.combo-plans') || request()->routeIs('admin.insurance-plans')),
                 empOpen: @json(request()->routeIs('admin.admin-management.*')),
                 doctorOpen: @json(request()->routeIs('admin.enrollment*') || request()->routeIs('admin.doctors*')),
                      policyOpen: @json(request()->routeIs('admin.policy-receipt.*')),
                      renewOpen: false,
                      legalOpen: @json(request()->routeIs('admin.cases')),
                      accountOpen: @json(request()->routeIs('admin.receipts')),
                      marketingOpen: @json(request()->routeIs('admin.call-sheet.*')),
                      dispatchedOpen: @json(request()->routeIs('admin.posts*')),
                     bulkOpen: @json(request()->routeIs('admin.bulk-upload.*')),
                      websiteOpen: false,
                 toggleMenu(menu) {
                    if (menu === 'mdm') this.mdmOpen = !this.mdmOpen;
                    if (menu === 'emp') this.empOpen = !this.empOpen;
                    if (menu === 'doctor') this.doctorOpen = !this.doctorOpen;
                    if (menu === 'policy') this.policyOpen = !this.policyOpen;
                          if (menu === 'renew') this.renewOpen = !this.renewOpen;
                          if (menu === 'legal') this.legalOpen = !this.legalOpen;
                          if (menu === 'account') this.accountOpen = !this.accountOpen;
                          if (menu === 'marketing') this.marketingOpen = !this.marketingOpen;
                          if (menu === 'dispatched') this.dispatchedOpen = !this.dispatchedOpen;
                          if (menu === 'bulk') this.bulkOpen = !this.bulkOpen;
                          if (menu === 'website') this.websiteOpen = !this.websiteOpen;
                 }
             }">
            <div class="mb-6 flex items-center justify-between rounded-2xl bg-white/10 px-4 py-3">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('assets/images/mediforum-logo.jpeg') }}" alt="MediForum logo" class="h-11 w-11 rounded-full border border-white/80 bg-white object-cover p-0.5">
                    <div>
                        <p class="brand-title text-lg font-bold text-white">MediForum</p>
                        <p class="text-xs text-blue-100">Admin Console</p>
                    </div>
                </div>
                <button class="text-white lg:hidden" @click="sidebarOpen = false"><i class="ri-close-line text-xl"></i></button>
            </div>

            <nav class="sidebar-menu min-h-0 flex-1 space-y-2 overflow-y-auto overscroll-contain pb-8 pr-1">
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
                            @php
                                $subAdminCount = \App\Models\User::whereIn('role', \App\Models\AdminRole::query()->pluck('role_key'))->count();
                            @endphp
                            <li>
                                <a href="{{ route('admin.admin-management.index') }}" class="has-badge {{ request()->routeIs('admin.admin-management.index') || request()->routeIs('admin.admin-management.create') || request()->routeIs('admin.admin-management.edit') ? 'active' : '' }}">
                                    <span class="submenu-left"><i class="ri-list-check-2"></i><span>Sub-Admin Management</span></span>
                                    <small class="menu-badge">{{ $subAdminCount }}</small>
                                </a>
                            </li>
                            <li><a href="{{ route('admin.admin-management.roles') }}" class="{{ request()->routeIs('admin.admin-management.roles') ? 'active' : '' }}"><span class="submenu-left"><i class="ri-list-check-2"></i><span>Role Management</span></span></a></li>
                        @else
                            <li><a href="#"><span class="submenu-left"><i class="ri-list-check-2"></i><span>Sub-Admin Management</span></span></a></li>
                            <li><a href="#"><span class="submenu-left"><i class="ri-list-check-2"></i><span>Role Management</span></span></a></li>
                        @endif
                    </ul>
                </div>

                <div class="treeview">
                    <button type="button" class="tree-toggle nav-link w-full {{ request()->routeIs('admin.enrollment*') || request()->routeIs('admin.doctors*') ? 'active' : '' }}" @click="toggleMenu('doctor')">
                        <span class="flex items-center gap-3">
                            <i class="ri-stethoscope-line"></i>
                            <span>Doctor Management</span>
                        </span>
                        <i class="ri-arrow-right-s-line tree-arrow" :class="{ 'rotate-90': doctorOpen }"></i>
                    </button>
                    <ul class="tree-menu" x-show="doctorOpen" x-transition.opacity x-cloak>
                        <li><a href="{{ route('admin.enrollment.create') }}" class="{{ request()->routeIs('admin.enrollment.create') ? 'active' : '' }}"><span class="submenu-left"><i class="ri-user-add-line"></i><span>Enrollment Entry</span></span></a></li>
                        <li><a href="{{ route('admin.enrollment') }}" class="{{ request()->routeIs('admin.enrollment*') ? 'active' : '' }}"><span class="submenu-left"><i class="ri-list-check-2"></i><span>Doctor List</span></span></a></li>
                        <li><a href="{{ route('admin.doctors.incomplete-documents') }}" class="{{ request()->routeIs('admin.doctors.incomplete-documents') ? 'active' : '' }}"><span class="submenu-left"><i class="ri-file-warning-line"></i><span>Incomplete Docs</span></span></a></li>
                        <li><a href="#"><span class="submenu-left"><i class="ri-list-check-2"></i><span>Membership nos.</span></span></a></li>
                    </ul>
                </div>

                <div class="treeview">
                    <button type="button" class="tree-toggle nav-link w-full" @click="toggleMenu('policy')">
                        <span class="flex items-center gap-3">
                            <i class="ri-file-list-3-line"></i>
                            <span>Policy Management</span>
                        </span>
                        <i class="ri-arrow-right-s-line tree-arrow" :class="{ 'rotate-90': policyOpen }"></i>
                    </button>
                    <ul class="tree-menu" x-show="policyOpen" x-transition.opacity x-cloak>
                        <li><a href="{{ route('admin.policy-receipt.index') }}" class="{{ request()->routeIs('admin.policy-receipt.*') ? 'active' : '' }}"><span class="submenu-left"><i class="ri-list-check-2"></i><span>Policy Received</span></span></a></li>
                        <li><a href="{{ route('admin.policy-receipt.doctors') }}" class=""><span class="submenu-left"><i class="ri-list-check-2"></i><span>Doctors policy</span></span></a></li>
                    </ul>
                </div>

                <div class="treeview">
                    <button type="button" class="tree-toggle nav-link w-full" @click="toggleMenu('renew')">
                        <span class="flex items-center gap-3">
                            <i class="ri-repeat-line"></i>
                            <span>Renew doctor</span>
                        </span>
                        <i class="ri-arrow-right-s-line tree-arrow" :class="{ 'rotate-90': renewOpen }"></i>
                    </button>
                    <ul class="tree-menu" x-show="renewOpen" x-transition.opacity x-cloak>
                        <li><a href="#"><span class="submenu-left"><i class="ri-list-check-2"></i><span>Renew doctor</span></span></a></li>
                    </ul>
                </div>

                <div class="treeview">
                    <button type="button" class="tree-toggle nav-link w-full {{ request()->routeIs('admin.cases') ? 'active' : '' }}" @click="toggleMenu('legal')">
                        <span class="flex items-center gap-3">
                            <i class="ri-scales-3-line"></i>
                            <span>Legal case management</span>
                        </span>
                        <i class="ri-arrow-right-s-line tree-arrow" :class="{ 'rotate-90': legalOpen }"></i>
                    </button>
                    <ul class="tree-menu" x-show="legalOpen" x-transition.opacity x-cloak>
                        <li><a href="{{ route('admin.cases') }}" class="{{ request()->routeIs('admin.cases') ? 'active' : '' }}"><span class="submenu-left"><i class="ri-list-check-2"></i><span>Case List</span></span></a></li>
                    </ul>
                </div>

                <div class="treeview">
                    <button type="button" class="tree-toggle nav-link w-full {{ request()->routeIs('admin.receipts') ? 'active' : '' }}" @click="toggleMenu('account')">
                        <span class="flex items-center gap-3">
                            <i class="ri-bank-card-line"></i>
                            <span>Account Management</span>
                        </span>
                        <i class="ri-arrow-right-s-line tree-arrow" :class="{ 'rotate-90': accountOpen }"></i>
                    </button>
                    <ul class="tree-menu" x-show="accountOpen" x-transition.opacity x-cloak>
                        <li><a href="{{ route('admin.receipts') }}" class="{{ request()->routeIs('admin.receipts') ? 'active' : '' }}"><span class="submenu-left"><i class="ri-list-check-2"></i><span>Money Reciept</span></span></a></li>
                        <li><a href="#"><span class="submenu-left"><i class="ri-list-check-2"></i><span>Premium Amount</span></span></a></li>
                        <li><a href="#"><span class="submenu-left"><i class="ri-list-check-2"></i><span>Enrollment cheque deposit</span></span></a></li>
                        <li><a href="#"><span class="submenu-left"><i class="ri-list-check-2"></i><span>Renewal cheque deposit</span></span></a></li>
                        <li><a href="#"><span class="submenu-left"><i class="ri-list-check-2"></i><span>Manage expense category</span></span></a></li>
                        <li><a href="#"><span class="submenu-left"><i class="ri-list-check-2"></i><span>Manage expense</span></span></a></li>
                        <li><a href="#"><span class="submenu-left"><i class="ri-list-check-2"></i><span>Manage salary</span></span></a></li>
                        <li><a href="#"><span class="submenu-left"><i class="ri-list-check-2"></i><span>Office expensions</span></span></a></li>
                    </ul>
                </div>

                <div class="treeview">
                    <button type="button" class="tree-toggle nav-link w-full {{ request()->routeIs('admin.call-sheet.*') ? 'active' : '' }}" @click="toggleMenu('marketing')">
                        <span class="flex items-center gap-3">
                            <i class="ri-megaphone-line"></i>
                            <span>Marketing</span>
                        </span>
                        <i class="ri-arrow-right-s-line tree-arrow" :class="{ 'rotate-90': marketingOpen }"></i>
                    </button>
                    <ul class="tree-menu" x-show="marketingOpen" x-transition.opacity x-cloak>
                        <li><a href="{{ route('admin.call-sheet.index') }}" class="{{ request()->routeIs('admin.call-sheet.*') ? 'active' : '' }}"><span class="submenu-left"><i class="ri-list-check-2"></i><span>Call sheet</span></span></a></li>
                    </ul>
                </div>

                <div class="treeview">
                    <button type="button" class="tree-toggle nav-link w-full {{ request()->routeIs('admin.posts*') ? 'active' : '' }}" @click="toggleMenu('dispatched')">
                        <span class="flex items-center gap-3">
                            <i class="ri-mail-send-line"></i>
                            <span>Dispatched post</span>
                        </span>
                        <i class="ri-arrow-right-s-line tree-arrow" :class="{ 'rotate-90': dispatchedOpen }"></i>
                    </button>
                    <ul class="tree-menu" x-show="dispatchedOpen" x-transition.opacity x-cloak>
                        <li><a href="{{ route('admin.posts') }}" class="{{ request()->routeIs('admin.posts*') ? 'active' : '' }}"><span class="submenu-left"><i class="ri-external-link-line"></i><span>Post List</span></span></a></li>
                    </ul>
                </div>

                <div class="treeview">
                    <button type="button" class="tree-toggle nav-link w-full {{ request()->routeIs('admin.bulk-upload.*') ? 'active' : '' }}" @click="toggleMenu('bulk')">
                        <span class="flex items-center gap-3">
                            <i class="ri-upload-cloud-2-line"></i>
                            <span>Bulk Upload</span>
                        </span>
                        <i class="ri-arrow-right-s-line tree-arrow" :class="{ 'rotate-90': bulkOpen }"></i>
                    </button>
                    <ul class="tree-menu" x-show="bulkOpen" x-transition.opacity x-cloak>
                        <li><a href="{{ route('admin.bulk-upload.index') }}" class="{{ request()->routeIs('admin.bulk-upload.*') ? 'active' : '' }}"><span class="submenu-left"><i class="ri-external-link-line"></i><span>Bulk Upload</span></span></a></li>
                    </ul>
                </div>

                <div class="treeview">
                    <button type="button" class="tree-toggle nav-link w-full" @click="toggleMenu('website')">
                        <span class="flex items-center gap-3">
                            <i class="ri-links-line"></i>
                            <span>Website Link</span>
                        </span>
                        <i class="ri-arrow-right-s-line tree-arrow" :class="{ 'rotate-90': websiteOpen }"></i>
                    </button>
                    <ul class="tree-menu" x-show="websiteOpen" x-transition.opacity x-cloak>
                        <li><a href="http://cms.nic.in/ncdrcusersWeb/courtroommodule.do?method=loadCaseHistory" target="_blank"><span class="submenu-left"><i class="ri-external-link-line"></i><span>Case History</span></span></a></li>
                        <li><a href="https://fir.kolkatapolice.org/" target="_blank"><span class="submenu-left"><i class="ri-external-link-line"></i><span>kolkatapolice.org</span></span></a></li>
                        <li><a href="http://www.dtdc.in/" target="_blank"><span class="submenu-left"><i class="ri-external-link-line"></i><span>DTDC</span></span></a></li>
                    </ul>
                </div>

                <a href="{{ route('admin.reports') }}" class="nav-link {{ request()->routeIs('admin.reports') ? 'active' : '' }}">
                    <i class="ri-bar-chart-2-line"></i>
                    <span>Reports</span>
                </a>
            </nav>
        </aside>

        <div x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-40 bg-slate-900/50 lg:hidden" @click="sidebarOpen = false" style="display: none;"></div>

        <div class="flex min-h-screen flex-1 flex-col min-w-0 overflow-hidden">
            <header class="topbar sticky top-0 z-30 flex h-16 items-center justify-between px-4 md:px-8">
                <div class="flex items-center gap-3">
                    <button class="rounded-xl border border-slate-200 bg-white p-2 text-slate-700 lg:hidden" @click="sidebarOpen = true">
                        <i class="ri-menu-2-line"></i>
                    </button>
                    <img src="{{ asset('assets/images/mediforum-logo.jpeg') }}" alt="MediForum logo" class="hidden h-9 w-9 rounded-full border border-slate-200 bg-white object-cover p-0.5 md:block">
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
    @stack('scripts')
</body>
</html>

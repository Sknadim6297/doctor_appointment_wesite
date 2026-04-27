<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - MediForum Admin</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('assets/images/mediforum-logo.jpeg') }}">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/css/admin-theme.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        .otp-backdrop {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(15, 23, 42, 0.7);
            z-index: 90;
            padding: 1rem;
        }
        .otp-backdrop.show {
            display: flex;
        }
        .otp-modal {
            width: min(100%, 430px);
            border-radius: 1rem;
            border: 1px solid #dbe4f2;
            background: #fff;
            box-shadow: 0 24px 64px rgba(2, 8, 23, 0.35);
            overflow: hidden;
        }
        .otp-head {
            padding: 0.95rem 1rem;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
            font-weight: 800;
            color: #0f172a;
        }
        .otp-body {
            padding: 1rem;
        }
        .otp-help {
            margin-bottom: 0.75rem;
            color: #475569;
            font-size: 0.86rem;
            line-height: 1.35;
        }
        .otp-input {
            width: 100%;
            border: 1px solid #cbd5e1;
            border-radius: 0.7rem;
            padding: 0.6rem 0.75rem;
            font-size: 0.95rem;
            letter-spacing: 0.2em;
            text-align: center;
            font-weight: 700;
        }
        .otp-input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
        }
        .otp-actions {
            margin-top: 0.9rem;
            display: flex;
            justify-content: space-between;
            gap: 0.5rem;
        }
        .otp-btn {
            border: 0;
            border-radius: 0.65rem;
            padding: 0.52rem 0.8rem;
            font-size: 0.8rem;
            font-weight: 700;
            cursor: pointer;
        }
        .otp-btn-secondary {
            background: #475569;
            color: #fff;
        }
        .otp-btn-primary {
            background: #2563eb;
            color: #fff;
        }
        .otp-btn-ghost {
            background: #e2e8f0;
            color: #1e293b;
        }
        .otp-status {
            min-height: 1.2rem;
            margin-top: 0.55rem;
            font-size: 0.8rem;
            color: #334155;
        }
        .otp-status.error {
            color: #dc2626;
        }
        .otp-status.success {
            color: #0f766e;
        }
    </style>
</head>
<body class="admin-shell" x-data="{ sidebarOpen: false }">
    <div class="flex min-h-screen overflow-hidden">

         <aside class="side-rail fixed inset-y-0 left-0 z-50 flex h-screen max-h-screen w-72 flex-shrink-0 flex-col overflow-hidden transform p-5 transition-transform duration-300 lg:static lg:translate-x-0"
             :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
             x-data="{}">
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

                @foreach($sidebarTree ?? [] as $node)
                    @include('admin.layouts.sidebar-node', ['node' => $node])
                @endforeach
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

    @php
        $requiresSensitiveOtp = auth()->check() && !in_array(auth()->user()->role, ['super_admin', 'admin'], true);
    @endphp

    <div id="sensitiveOtpModal" class="otp-backdrop" aria-hidden="true">
        <div class="otp-modal" role="dialog" aria-modal="true" aria-labelledby="otpModalTitle">
            <div class="otp-head" id="otpModalTitle">OTP verification required</div>
            <div class="otp-body">
                <p class="otp-help">To view sensitive details, verify with OTP sent to your registered email/mobile.</p>
                <input id="sensitiveOtpInput" class="otp-input" type="text" inputmode="numeric" maxlength="6" placeholder="Enter 6-digit OTP">
                <div id="sensitiveOtpStatus" class="otp-status"></div>
                <div class="otp-actions">
                    <button type="button" class="otp-btn otp-btn-ghost" id="sensitiveOtpCancel">Cancel</button>
                    <button type="button" class="otp-btn otp-btn-secondary" id="sensitiveOtpResend">Resend OTP</button>
                    <button type="button" class="otp-btn otp-btn-primary" id="sensitiveOtpVerify">Verify & Continue</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const requiresSensitiveOtp = @json($requiresSensitiveOtp);
            if (!requiresSensitiveOtp) {
                return;
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const requestUrl = @json(route('admin.sensitive-otp.request'));
            const verifyUrl = @json(route('admin.sensitive-otp.verify'));

            const modal = document.getElementById('sensitiveOtpModal');
            const input = document.getElementById('sensitiveOtpInput');
            const statusBox = document.getElementById('sensitiveOtpStatus');
            const cancelBtn = document.getElementById('sensitiveOtpCancel');
            const resendBtn = document.getElementById('sensitiveOtpResend');
            const verifyBtn = document.getElementById('sensitiveOtpVerify');

            let currentSubjectType = 'enrollment';
            let currentSubjectId = null;
            let redirectUrl = '';

            function setStatus(message, type) {
                statusBox.textContent = message || '';
                statusBox.className = 'otp-status' + (type ? ' ' + type : '');
            }

            function toggleButtons(disabled) {
                [cancelBtn, resendBtn, verifyBtn].forEach((button) => {
                    if (button) {
                        button.disabled = disabled;
                    }
                });
            }

            async function postJson(url, payload) {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });

                const data = await response.json().catch(() => ({}));
                if (!response.ok) {
                    throw new Error(data.message || 'Request failed.');
                }

                return data;
            }

            async function requestOtp() {
                if (!currentSubjectId || !redirectUrl) {
                    return;
                }

                toggleButtons(true);
                setStatus('Sending OTP...', '');

                try {
                    const result = await postJson(requestUrl, {
                        subject_type: currentSubjectType,
                        subject_id: currentSubjectId,
                        redirect_url: redirectUrl,
                    });

                    setStatus(result.message || 'OTP sent.', 'success');
                    input.value = '';
                    input.focus();
                } catch (error) {
                    setStatus(error.message || 'Unable to send OTP.', 'error');
                } finally {
                    toggleButtons(false);
                }
            }

            async function verifyOtp() {
                const otp = input.value.trim();
                if (!/^\d{6}$/.test(otp)) {
                    setStatus('Please enter a valid 6-digit OTP.', 'error');
                    return;
                }

                toggleButtons(true);
                setStatus('Verifying OTP...', '');

                try {
                    const result = await postJson(verifyUrl, {
                        subject_type: currentSubjectType,
                        subject_id: currentSubjectId,
                        otp,
                        redirect_url: redirectUrl,
                    });

                    setStatus('OTP verified. Redirecting...', 'success');
                    window.location.href = result.redirect_url || redirectUrl;
                } catch (error) {
                    setStatus(error.message || 'OTP verification failed.', 'error');
                } finally {
                    toggleButtons(false);
                }
            }

            function closeModal() {
                modal.classList.remove('show');
                modal.setAttribute('aria-hidden', 'true');
                currentSubjectId = null;
                redirectUrl = '';
                setStatus('', '');
            }

            function openModal(subjectId, nextUrl) {
                currentSubjectId = subjectId;
                redirectUrl = nextUrl;
                modal.classList.add('show');
                modal.setAttribute('aria-hidden', 'false');
                requestOtp();
            }

            document.addEventListener('click', function (event) {
                const link = event.target.closest('a[href]');
                if (!link) {
                    return;
                }

                const href = link.getAttribute('href');
                if (!href || href.startsWith('#') || href.startsWith('javascript:')) {
                    return;
                }

                const absoluteUrl = new URL(href, window.location.origin);
                const doctorMatch = absoluteUrl.pathname.match(/^\/admin\/doctors\/(\d+)$/);
                const receiptViewMatch = absoluteUrl.pathname.match(/^\/admin\/receipts\/(\d+)\/view$/);
                const subjectId = doctorMatch ? Number(doctorMatch[1]) : (receiptViewMatch ? Number(receiptViewMatch[1]) : null);

                if (!subjectId) {
                    return;
                }

                event.preventDefault();
                openModal(subjectId, absoluteUrl.toString());
            });

            cancelBtn.addEventListener('click', closeModal);
            resendBtn.addEventListener('click', requestOtp);
            verifyBtn.addEventListener('click', verifyOtp);

            input.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    verifyOtp();
                }
            });

            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeModal();
                }
            });
        })();
    </script>

    @stack('scripts')
</body>
</html>

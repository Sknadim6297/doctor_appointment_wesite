<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - MediForum</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('assets/images/mediforum-logo.jpeg') }}">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/css/admin-theme.css', 'resources/js/app.js'])
</head>
<body class="login-viewport flex items-center justify-center px-5">
    <section class="login-card">
        <div class="mb-7 text-center">
            <img src="{{ asset('assets/images/mediforum-logo.jpeg') }}" alt="MediForum logo" class="mx-auto mb-4 h-20 w-20 rounded-full border border-slate-200 bg-white object-cover p-1 shadow-lg shadow-blue-100">
            <h1 class="page-title text-3xl font-bold text-slate-900">Welcome Back</h1>
            <p class="mt-2 text-sm text-slate-600">Doctor Enrollment & Membership Control Center</p>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login.post') }}" class="space-y-4">
            @csrf
            <div>
                <label for="email" class="mb-1.5 block text-sm font-semibold text-slate-700">Email</label>
                <div class="relative">
                    <i class="ri-mail-line pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus class="login-input pl-10" placeholder="admin@mediforum.com">
                </div>
            </div>

            <div>
                <label for="password" class="mb-1.5 block text-sm font-semibold text-slate-700">Password</label>
                <div class="relative">
                    <i class="ri-lock-2-line pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input id="password" name="password" type="password" required class="login-input pl-10" placeholder="Enter password">
                </div>
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                <span>Keep me signed in</span>
            </label>

            <button type="submit" class="btn-brand w-full">
                <i class="ri-login-box-line"></i>
                <span>Sign In Securely</span>
            </button>
        </form>

        <p class="mt-6 text-center text-xs text-slate-500">&copy; {{ date('Y') }} MediForum. Premium Admin Platform.</p>
    </section>
</body>
</html>

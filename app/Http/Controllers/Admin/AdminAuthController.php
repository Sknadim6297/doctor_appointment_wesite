<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLoginLog;
use App\Models\User;
use App\Services\AdminAccessService;
use App\Services\ClientContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
    public function __construct(
        private readonly AdminAccessService $adminAccessService,
        private readonly ClientContextService $clientContextService
    ) {
    }

    public function showLoginForm()
    {
        $allowedRoles = $this->adminAccessService->allowedAdminRoles();

        if (Auth::check() && in_array(Auth::user()->role, $allowedRoles, true)) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $credentials = $request->only('email', 'password');
        $remember = $request->has('remember');

        if (Auth::attempt($credentials, $remember)) {
            /** @var User $user */
            $user = Auth::user();
            $allowedRoles = $this->adminAccessService->allowedAdminRoles();
            
            if (!in_array($user->role, $allowedRoles, true) && empty($user->adminRoleKeys())) {
                Auth::logout();
                return back()->withErrors(['email' => 'You do not have admin access.']);
            }

            if (!$user->is_active) {
                Auth::logout();
                return back()->withErrors(['email' => 'Your account has been deactivated.']);
            }

            $request->session()->regenerate();

            $this->adminAccessService->syncPrivilegeCatalogForUser($user);
            $context = $this->clientContextService->fromRequest($request);

            AdminLoginLog::create([
                'user_id' => $user->id,
                'session_id' => $context['session_id'],
                'ip_address' => $context['ip_address'],
                'user_agent' => $context['user_agent'],
                'device_type' => $context['device_type'],
                'device_name' => $context['device_name'],
                'browser_name' => $context['browser_name'],
                'browser_version' => $context['browser_version'],
                'logged_in_at' => now(),
            ]);

            return redirect()->intended(route('admin.dashboard'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->withInput($request->only('email'));
    }

    public function logout(Request $request)
    {
        /** @var User|null $user */
        $user = Auth::user();
        $sessionId = $request->hasSession() ? $request->session()->getId() : null;

        if ($user && $sessionId) {
            AdminLoginLog::query()
                ->where('user_id', $user->id)
                ->where('session_id', $sessionId)
                ->whereNull('logged_out_at')
                ->latest('logged_in_at')
                ->limit(1)
                ->update([
                    'logged_out_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }
}


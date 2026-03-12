<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLoginLog;
use App\Models\AdminRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
    public function showLoginForm()
    {
        $allowedRoles = $this->allowedAdminRoles();

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
            $user = Auth::user();
            $allowedRoles = $this->allowedAdminRoles();
            
            if (!in_array($user->role, $allowedRoles, true)) {
                Auth::logout();
                return back()->withErrors(['email' => 'You do not have admin access.']);
            }

            if (!$user->is_active) {
                Auth::logout();
                return back()->withErrors(['email' => 'Your account has been deactivated.']);
            }

            AdminLoginLog::create([
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
                'logged_in_at' => now(),
            ]);

            $request->session()->regenerate();
            return redirect()->intended(route('admin.dashboard'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->withInput($request->only('email'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }

    private function allowedAdminRoles(): array
    {
        $roleKeys = AdminRole::query()->pluck('role_key')->all();

        return array_values(array_unique(array_merge(['super_admin', 'admin'], $roleKeys)));
    }
}


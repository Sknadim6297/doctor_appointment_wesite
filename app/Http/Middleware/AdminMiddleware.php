<?php

namespace App\Http\Middleware;

use App\Models\AdminRole;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('admin.login');
        }

        $user = Auth::user();

        if (!in_array($user->role, $this->allowedAdminRoles(), true)) {
            Auth::logout();
            return redirect()->route('admin.login')->withErrors(['error' => 'Unauthorized access.']);
        }

        if (!$user->is_active) {
            Auth::logout();
            return redirect()->route('admin.login')->withErrors(['error' => 'Your account is inactive.']);
        }

        return $next($request);
    }

    private function allowedAdminRoles(): array
    {
        $roleKeys = AdminRole::query()->pluck('role_key')->all();

        return array_values(array_unique(array_merge(['super_admin', 'admin'], $roleKeys)));
    }
}

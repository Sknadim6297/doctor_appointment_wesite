<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminMiddleware
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

        /** @var User $user */
        $user = Auth::user();

        if (!$user->hasAdminRole('super_admin')) {
            abort(403, 'Unauthorized action. Super Admin access required.');
        }

        if (!$user->is_active) {
            Auth::logout();
            return redirect()->route('admin.login')->withErrors(['error' => 'Your account is inactive.']);
        }

        return $next($request);
    }
}

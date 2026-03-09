<?php

namespace App\Http\Middleware;

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

        if (!in_array($user->role, ['admin', 'super_admin'])) {
            Auth::logout();
            return redirect()->route('admin.login')->withErrors(['error' => 'Unauthorized access.']);
        }

        if (!$user->is_active) {
            Auth::logout();
            return redirect()->route('admin.login')->withErrors(['error' => 'Your account is inactive.']);
        }

        return $next($request);
    }
}

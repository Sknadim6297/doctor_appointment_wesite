<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\AdminAccessService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function __construct(private readonly AdminAccessService $adminAccessService)
    {
    }

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

        if (!in_array($user->role, $this->adminAccessService->allowedAdminRoles(), true) && empty($user->adminRoleKeys())) {
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

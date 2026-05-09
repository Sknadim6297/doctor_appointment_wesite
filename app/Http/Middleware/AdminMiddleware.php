<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\AdminAccessService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
        $user = $this->resolveUser($request);

        Log::debug('AdminMiddleware auth check', [
            'path' => $request->path(),
            'route' => optional($request->route())->getName(),
            'guard' => Auth::getDefaultDriver(),
            'user_id' => $user?->id,
            'user_role' => $user?->role,
            'is_authenticated' => (bool) $user,
        ]);

        if (!$user) {
            return redirect()->route('admin.login');
        }

        if (!in_array($user->role, $this->adminAccessService->allowedAdminRoles(), true) && empty($user->adminRoleKeys())) {
            Auth::logout();
            Log::warning('AdminMiddleware rejected user due to role mismatch', [
                'path' => $request->path(),
                'route' => optional($request->route())->getName(),
                'user_id' => $user->id,
                'user_role' => $user->role,
                'allowed_roles' => $this->adminAccessService->allowedAdminRoles(),
                'resolved_roles' => $user->adminRoleKeys(),
            ]);
            return redirect()->route('admin.login')->withErrors(['error' => 'Unauthorized access.']);
        }

        if (!$user->is_active) {
            Auth::logout();
            Log::warning('AdminMiddleware rejected inactive user', [
                'path' => $request->path(),
                'route' => optional($request->route())->getName(),
                'user_id' => $user->id,
                'user_role' => $user->role,
            ]);
            return redirect()->route('admin.login')->withErrors(['error' => 'Your account is inactive.']);
        }

        return $next($request);
    }

    private function resolveUser(Request $request): ?User
    {
        return $request->user() ?? Auth::guard(Auth::getDefaultDriver())->user() ?? Auth::user();
    }
}

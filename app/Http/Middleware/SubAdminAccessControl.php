<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\AdminAccessService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SubAdminAccessControl
{
    public function __construct(private readonly AdminAccessService $adminAccessService)
    {
    }

    /**
     * Verify that only the assigned sub-admin can access a specific module.
     * 
     * Usage in routes:
     *   Route::post('enrollment', [EnrollmentController::class, 'store'])
     *       ->middleware('sub-admin.access-control:enrollment-entry');
     * 
     * The middleware parameter should be the sidebar key (e.g., 'enrollment-entry')
     */
    public function handle(Request $request, Closure $next, string $sidebarKey = ''): Response
    {
        $user = $this->resolveUser($request);

        // Super admins always have access
        if (!$user || $user->role === 'super_admin') {
            return $next($request);
        }

        // Non-super-admins need explicit permission
        if (empty($sidebarKey)) {
            abort(403, 'Access control misconfiguration: sidebar key not provided.');
        }

        // Resolve the most likely permission keys so the middleware can tolerate
        // existing catalog differences without blocking legitimate owners.
        $permissionKeys = array_values(array_unique(array_filter([
            $sidebarKey,
            str_starts_with($sidebarKey, 'sidebar.') ? $sidebarKey : null,
            'sidebar.doctor-management.' . $sidebarKey,
            'sidebar.' . $sidebarKey,
        ])));

        $hasAccess = false;
        foreach ($permissionKeys as $permissionKey) {
            if ($this->adminAccessService->hasPrivilege($user, $permissionKey, 'view')) {
                $hasAccess = true;
                break;
            }
        }

        if (!$hasAccess) {
            Log::warning('SubAdminAccessControl denied access', [
                'path' => $request->path(),
                'route' => optional($request->route())->getName(),
                'user_id' => $user->id,
                'user_role' => $user->role,
                'sidebar_key' => $sidebarKey,
                'permission_keys' => $permissionKeys,
            ]);

            abort(403, "You do not have permission to access this module. Only the assigned sub-admin for '{$sidebarKey}' can access it.");
        }

        return $next($request);
    }

    private function resolveUser(Request $request): ?User
    {
        return $request->user() ?? Auth::guard(Auth::getDefaultDriver())->user() ?? Auth::user();
    }
}

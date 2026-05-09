<?php

namespace App\Http\Middleware;

use App\Services\AdminAccessService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AdminPrivilegeMiddleware
{
    public function __construct(private readonly AdminAccessService $adminAccessService)
    {
    }

    public function handle(Request $request, Closure $next, string $pageKey, string $actionKey = 'view'): Response
    {
        $user = $this->resolveUser($request);

        $hasPriv = $user ? $this->adminAccessService->hasPrivilege($user, $pageKey, $actionKey) : false;
        Log::debug('AdminPrivilegeMiddleware check', [
            'path' => $request->path(),
            'route' => optional($request->route())->getName(),
            'guard' => Auth::getDefaultDriver(),
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'user_email' => $user?->email,
            'user_role' => $user?->role,
            'pageKey' => $pageKey,
            'actionKey' => $actionKey,
            'hasPrivilege' => $hasPriv,
        ]);

        if (!$user || !$hasPriv) {
            Log::warning('AdminPrivilegeMiddleware denied access', [
                'path' => $request->path(),
                'route' => optional($request->route())->getName(),
                'guard' => Auth::getDefaultDriver(),
                'user_id' => $user?->id,
                'user_role' => $user?->role,
                'pageKey' => $pageKey,
                'actionKey' => $actionKey,
            ]);

            abort(403, 'You do not have permission to perform this action.');
        }

        return $next($request);
    }

    private function resolveUser(Request $request): ?\App\Models\User
    {
        return $request->user() ?? Auth::guard(Auth::getDefaultDriver())->user() ?? Auth::user();
    }
}
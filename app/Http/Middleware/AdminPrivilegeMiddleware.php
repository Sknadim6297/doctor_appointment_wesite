<?php

namespace App\Http\Middleware;

use App\Services\AdminAccessService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminPrivilegeMiddleware
{
    public function __construct(private readonly AdminAccessService $adminAccessService)
    {
    }

    public function handle(Request $request, Closure $next, string $pageKey, string $actionKey = 'view'): Response
    {
        $user = Auth::user();

        if (!$user || !$this->adminAccessService->hasPrivilege($user, $pageKey, $actionKey)) {
            abort(403, 'You do not have permission to perform this action.');
        }

        return $next($request);
    }
}
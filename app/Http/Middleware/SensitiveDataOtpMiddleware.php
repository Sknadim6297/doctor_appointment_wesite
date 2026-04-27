<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SensitiveDataOtpMiddleware
{
    public function handle(Request $request, Closure $next, string $subjectType = 'enrollment', string $routeParam = 'id'): Response
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (!$user || !$this->requiresSensitiveOtp($user)) {
            return $next($request);
        }

        $subjectId = $this->resolveSubjectId($request, $routeParam);
        if ($subjectId <= 0) {
            return $next($request);
        }

        $sessionKey = 'sensitive_otp.' . $subjectType . '.' . $subjectId;
        $sessionExpiry = (string) $request->session()->get($sessionKey, '');

        if ($sessionExpiry !== '') {
            try {
                if (Carbon::parse($sessionExpiry)->isFuture()) {
                    return $next($request);
                }
            } catch (\Throwable) {
                // Ignore malformed session value and continue to OTP-required response.
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'OTP verification required before accessing sensitive details.',
                'otp_required' => true,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
            ], 423);
        }

        return redirect()
            ->route('admin.doctors.index')
            ->with('error', 'OTP verification is required to access this sensitive detail.');
    }

    private function requiresSensitiveOtp(User $user): bool
    {
        return !in_array($user->role, ['super_admin', 'admin'], true);
    }

    private function resolveSubjectId(Request $request, string $routeParam): int
    {
        $raw = $request->route($routeParam);

        if (is_object($raw) && isset($raw->id)) {
            return (int) $raw->id;
        }

        return (int) $raw;
    }
}

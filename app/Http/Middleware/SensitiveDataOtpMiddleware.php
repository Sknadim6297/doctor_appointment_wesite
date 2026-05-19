<?php

namespace App\Http\Middleware;

use App\Models\Enrollment;
use App\Models\User;
use App\Services\EnrollmentEditAccessService;
use App\Services\EnrollmentRecordAccessService;
use App\Support\EnrollmentWorkflow;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SensitiveDataOtpMiddleware
{
    public function handle(
        Request $request,
        Closure $next,
        string $subjectType = 'enrollment',
        string $routeParam = 'id',
        string $accessAction = 'view',
    ): Response {
        /** @var User|null $user */
        $user = Auth::user();

        if (!$user || !$this->requiresSensitiveOtp($user)) {
            return $next($request);
        }

        $subjectId = $this->resolveSubjectId($request, $routeParam);
        if ($subjectId <= 0) {
            return $next($request);
        }

        if ($this->shouldBypassEnrollmentOtp($request, $user, $routeParam, $accessAction)) {
            return $next($request);
        }

        $sessionKey = $this->sessionKey($subjectType, $subjectId, $accessAction);
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
                'access_action' => $accessAction,
            ], 423);
        }

        // For HTML requests: flash sensitive OTP info so the front-end can open
        // the OTP modal in-place and initiate the OTP request flow. Fall back
        // to the doctors index if there is no referer to return to.
        $payload = [
            'required' => true,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'access_action' => $accessAction,
            'redirect_url' => $request->fullUrl(),
        ];

        $previous = url()->previous();
        if (!$previous || $previous === url()->current()) {
            return redirect()
                ->route('admin.doctors.index')
                ->with('sensitive_otp', $payload);
        }

        return redirect()->to($previous)->with('sensitive_otp', $payload);
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

    private function sessionKey(string $subjectType, int $subjectId, string $accessAction): string
    {
        return 'sensitive_otp.' . $subjectType . '.' . $subjectId . '.' . $accessAction;
    }

    private function shouldBypassEnrollmentOtp(
        Request $request,
        User $user,
        string $routeParam,
        string $accessAction,
    ): bool {
        $enrollment = $this->resolveEnrollment($request, $routeParam);
        if (!$enrollment) {
            return false;
        }

        $recordAccess = app(EnrollmentRecordAccessService::class);
        if (!$recordAccess->ownsRecord($user, $enrollment)) {
            return false;
        }

        if (EnrollmentWorkflow::canContinueDraftEntry($enrollment)) {
            return true;
        }

        if ($accessAction === 'edit') {
            $editAccess = app(EnrollmentEditAccessService::class);

            return !$editAccess->requiresOtpGuardForUser($enrollment, $user);
        }

        return false;
    }

    private function resolveEnrollment(Request $request, string $routeParam): ?Enrollment
    {
        $raw = $request->route($routeParam);

        if ($raw instanceof Enrollment) {
            return $raw;
        }

        $id = is_numeric($raw) ? (int) $raw : 0;

        return $id > 0 ? Enrollment::query()->find($id) : null;
    }
}

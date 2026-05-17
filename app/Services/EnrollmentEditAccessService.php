<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\EnrollmentEditAccessSession;
use App\Models\User;
use App\Notifications\EnrollmentEditAccessOtpNotification;
use App\Support\EnrollmentWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class EnrollmentEditAccessService
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
    ) {
    }

    public function requiresOtpGuard(Enrollment $enrollment): bool
    {
        $wf = EnrollmentWorkflow::normalize($enrollment->workflow_status);

        if ($wf === EnrollmentWorkflow::RETURNED_FOR_CORRECTION) {
            return false;
        }

        if ($wf === EnrollmentWorkflow::DRAFT) {
            return false;
        }

        if ($wf === EnrollmentWorkflow::IN_PROGRESS && (int) ($enrollment->current_step ?? 1) <= 1) {
            return false;
        }

        if ($enrollment->status === 'approved') {
            return true;
        }

        if ($wf === EnrollmentWorkflow::COMPLETED && !$enrollment->is_step_incomplete) {
            return true;
        }

        if ($enrollment->status === 'pending' && in_array($wf, EnrollmentWorkflow::gateStatuses(), true)) {
            return true;
        }

        if ($enrollment->status === 'pending'
            && $wf === EnrollmentWorkflow::IN_PROGRESS
            && (int) ($enrollment->current_step ?? 1) >= 2) {
            return true;
        }

        return false;
    }

    public function expireStaleSessions(): void
    {
        $now = now();

        EnrollmentEditAccessSession::query()
            ->where('status', EnrollmentEditAccessSession::STATUS_ACTIVE)
            ->whereNotNull('session_expires_at')
            ->where('session_expires_at', '<', $now)
            ->update(['status' => EnrollmentEditAccessSession::STATUS_EXPIRED]);

        EnrollmentEditAccessSession::query()
            ->where('status', EnrollmentEditAccessSession::STATUS_PENDING_OTP)
            ->whereNotNull('otp_expires_at')
            ->where('otp_expires_at', '<', $now)
            ->update(['status' => EnrollmentEditAccessSession::STATUS_EXPIRED]);
    }

    public function activeSessionFor(Enrollment $enrollment, User $user): ?EnrollmentEditAccessSession
    {
        $this->expireStaleSessions();

        return EnrollmentEditAccessSession::query()
            ->where('enrollment_id', $enrollment->id)
            ->where('requested_by_user_id', $user->id)
            ->where('status', EnrollmentEditAccessSession::STATUS_ACTIVE)
            ->whereNotNull('session_expires_at')
            ->where('session_expires_at', '>', now())
            ->orderByDesc('id')
            ->first();
    }

    public function pendingOtpSessionForEnrollment(Enrollment $enrollment): ?EnrollmentEditAccessSession
    {
        $this->expireStaleSessions();

        return EnrollmentEditAccessSession::query()
            ->where('enrollment_id', $enrollment->id)
            ->where('status', EnrollmentEditAccessSession::STATUS_PENDING_OTP)
            ->whereNotNull('otp_expires_at')
            ->where('otp_expires_at', '>', now())
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @return array{locked: bool, session_active: bool, session_expires_at: ?\Illuminate\Support\Carbon, pending_otp: bool, requester: ?User, can_request: bool}
     */
    public function viewState(Enrollment $enrollment, ?User $viewer): array
    {
        $this->expireStaleSessions();

        $locked = $viewer && !$this->isSuperAdmin($viewer) && $this->requiresOtpGuard($enrollment);
        $session = $viewer ? $this->activeSessionFor($enrollment, $viewer) : null;
        $pending = $this->pendingOtpSessionForEnrollment($enrollment);

        return [
            'locked' => (bool) $locked,
            'session_active' => (bool) $session,
            'session_expires_at' => $session?->session_expires_at,
            'pending_otp' => (bool) $pending,
            'requester' => $pending?->requester,
            'can_request' => (bool) ($viewer && !$this->isSuperAdmin($viewer) && $locked && $this->canUserRequest($viewer, $enrollment)),
        ];
    }

    public function canUserRequest(User $user, Enrollment $enrollment): bool
    {
        if ($this->isSuperAdmin($user)) {
            return false;
        }

        if (!$this->requiresOtpGuard($enrollment)) {
            return false;
        }

        $uid = (int) $user->id;

        return (int) $enrollment->created_by === $uid || (int) $enrollment->agent_id === $uid;
    }

    public function requestAccess(Request $request, Enrollment $enrollment, User $requester): array
    {
        if ($this->isSuperAdmin($requester)) {
            return ['success' => false, 'message' => 'Super Admin has direct edit access and does not need edit access requests.'];
        }

        if (!$this->requiresOtpGuard($enrollment)) {
            return ['success' => false, 'message' => 'This enrollment does not require an edit access request.'];
        }

        if (!$this->canUserRequest($requester, $enrollment)) {
            return ['success' => false, 'message' => 'You are not allowed to request edit access for this enrollment.'];
        }

        $rateKey = 'enrollment-edit-access-request:' . $enrollment->id . ':' . $requester->id;
        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            return ['success' => false, 'message' => 'Too many requests. Please try again later.'];
        }

        RateLimiter::hit($rateKey, 3600);

        $otp = (string) random_int(100000, 999999);
        $otpMinutes = max(3, (int) config('enrollment_edit_access.otp_valid_minutes', 10));

        $admins = $this->otpRecipientUsers();

        if ($admins->isEmpty()) {
            return ['success' => false, 'message' => 'No administrator email is configured to receive the OTP.'];
        }

        $session = null;

        DB::transaction(function () use ($enrollment, $requester, $otp, $otpMinutes, &$session): void {
            EnrollmentEditAccessSession::query()
                ->where('enrollment_id', $enrollment->id)
                ->where('status', EnrollmentEditAccessSession::STATUS_PENDING_OTP)
                ->update(['status' => EnrollmentEditAccessSession::STATUS_SUPERSEDED]);

            $session = EnrollmentEditAccessSession::query()->create([
                'enrollment_id' => $enrollment->id,
                'requested_by_user_id' => $requester->id,
                'otp_hash' => Hash::make($otp),
                'otp_expires_at' => now()->addMinutes($otpMinutes),
                'otp_failed_attempts' => 0,
                'status' => EnrollmentEditAccessSession::STATUS_PENDING_OTP,
            ]);
        });

        foreach ($admins as $admin) {
            if (!empty($admin->email)) {
                $admin->notify(new EnrollmentEditAccessOtpNotification($otp, $enrollment, $requester, $otpMinutes));
            }
        }

        $this->activityLogService->log(
            $request,
            'enrollment',
            'edit_access_requested',
            $enrollment,
            $requester,
            'Requested temporary edit access (OTP sent to administrators).',
            [
                'session_id' => $session?->id,
                'otp_expires_at' => $session?->otp_expires_at?->toIso8601String(),
                'recipient_count' => $admins->count(),
            ]
        );

        return [
            'success' => true,
            'message' => 'An OTP has been sent to administrator email addresses. Ask an administrator to verify it in the enrollment dossier.',
            'otp_expires_at' => $session?->otp_expires_at?->toIso8601String(),
        ];
    }

    public function verifyOtp(Request $request, Enrollment $enrollment, User $admin, string $otp): array
    {
        if (!$this->isPrivilegedAdmin($admin)) {
            return ['success' => false, 'message' => 'Only administrators can verify this OTP.'];
        }

        $this->expireStaleSessions();

        $session = $this->pendingOtpSessionForEnrollment($enrollment);

        if (!$session || !$session->otp_hash) {
            return ['success' => false, 'message' => 'No pending edit access request or OTP has expired.'];
        }

        if (now()->greaterThan($session->otp_expires_at)) {
            $session->update(['status' => EnrollmentEditAccessSession::STATUS_EXPIRED]);

            return ['success' => false, 'message' => 'OTP has expired. Ask the requester to submit a new request.'];
        }

        if (!Hash::check($otp, $session->otp_hash)) {
            $fails = (int) $session->otp_failed_attempts + 1;
            $session->forceFill(['otp_failed_attempts' => $fails])->save();

            $max = (int) config('enrollment_edit_access.max_otp_attempts', 5);
            if ($fails >= $max) {
                $session->update(['status' => EnrollmentEditAccessSession::STATUS_CANCELLED]);
            }

            return ['success' => false, 'message' => 'Invalid OTP.'];
        }

        $sessionMinutes = max(15, (int) config('enrollment_edit_access.edit_session_minutes', 60));

        $session->forceFill([
            'status' => EnrollmentEditAccessSession::STATUS_ACTIVE,
            'verified_at' => now(),
            'granted_by_user_id' => $admin->id,
            'session_expires_at' => now()->addMinutes($sessionMinutes),
            'otp_hash' => null,
        ])->save();

        $this->activityLogService->log(
            $request,
            'enrollment',
            'edit_access_otp_verified',
            $enrollment,
            $admin,
            'Verified enrollment edit access OTP and started temporary edit session for requester.',
            [
                'session_id' => $session->id,
                'requester_user_id' => $session->requested_by_user_id,
                'verified_at' => $session->verified_at?->toIso8601String(),
                'session_expires_at' => $session->session_expires_at?->toIso8601String(),
            ]
        );

        return [
            'success' => true,
            'message' => 'Edit access granted for ' . $sessionMinutes . ' minutes.',
            'session_expires_at' => $session->session_expires_at?->toIso8601String(),
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function otpRecipientUsers()
    {
        return User::query()
            ->whereIn('role', ['admin', 'super_admin'])
            ->where('is_active', true)
            ->whereNotNull('email')
            ->orderByRaw("FIELD(role, 'super_admin', 'admin')")
            ->get();
    }

    private function isPrivilegedAdmin(?User $user): bool
    {
        return (bool) ($user && (
            in_array(($user->role ?? null), ['admin', 'super_admin'], true) ||
            (method_exists($user, 'hasAdminRole') && $user->hasAdminRole(['admin', 'super_admin']))
        ));
    }

    private function isSuperAdmin(?User $user): bool
    {
        return (bool) ($user && (
            (($user->role ?? null) === 'super_admin') ||
            (method_exists($user, 'hasAdminRole') && $user->hasAdminRole('super_admin'))
        ));
    }

    public function assertMayPerformEdit(Request $request, Enrollment $enrollment, User $user): ?\Illuminate\Http\RedirectResponse
    {
        if ($this->isSuperAdmin($user)) {
            return null;
        }

        if (!$this->requiresOtpGuard($enrollment)) {
            return null;
        }

        if ($this->activeSessionFor($enrollment, $user)) {
            return null;
        }

        return redirect()
            ->route('admin.enrollment.details', $enrollment->id)
            ->with('error', 'This enrollment is view-only. Request edit access and ask an administrator to verify the OTP before editing.');
    }

    public function assertMayPerformEditJson(Request $request, Enrollment $enrollment, User $user): ?\Illuminate\Http\JsonResponse
    {
        if ($this->isSuperAdmin($user)) {
            return null;
        }

        if (!$this->requiresOtpGuard($enrollment)) {
            return null;
        }

        if ($this->activeSessionFor($enrollment, $user)) {
            return null;
        }

        return response()->json([
            'success' => false,
            'message' => 'Edit access required. Request access from the enrollment details page.',
            'edit_access_required' => true,
        ], 403);
    }
}

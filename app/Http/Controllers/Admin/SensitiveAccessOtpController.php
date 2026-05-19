<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\OtpVerificationLog;
use App\Models\User;
use App\Notifications\SensitiveAccessOtpNotification;
use App\Services\ActivityLogService;
use App\Services\EnrollmentEditAccessService;
use App\Services\EnrollmentRecordAccessService;
use App\Support\EnrollmentWorkflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Throwable;

class SensitiveAccessOtpController extends Controller
{
    public function __construct(
        private readonly EnrollmentRecordAccessService $recordAccess,
        private readonly EnrollmentEditAccessService $editAccess,
        private readonly ActivityLogService $activityLogService,
    ) {
    }

    public function requestOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subject_type' => 'required|string|in:enrollment',
            'subject_id' => 'required|integer|min:1',
            'redirect_url' => 'required|url',
            'access_action' => 'nullable|string|in:view,edit,documents',
        ]);

        /** @var User|null $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (!$this->requiresSensitiveOtp($user)) {
            return response()->json(['message' => 'OTP verification is not required for this user.']);
        }

        $accessAction = (string) ($data['access_action'] ?? 'view');
        $subject = $this->findSubject($data['subject_type'], (int) $data['subject_id']);

        if (!$subject) {
            return response()->json(['message' => 'Unable to find requested record.'], 404);
        }

        /** @var Enrollment $enrollment */
        $enrollment = $subject['model'];
        $this->recordAccess->assertCanAccessRecord($user, $enrollment);

        if ($this->shouldBypassSensitiveOtp($user, $enrollment, $accessAction)) {
            return response()->json([
                'message' => 'OTP verification is not required for this enrollment.',
                'otp_not_required' => true,
                'redirect_url' => $data['redirect_url'],
            ]);
        }

        if (blank($user->email) && blank($user->phone)) {
            return response()->json([
                'message' => 'No registered email or mobile is available for OTP delivery.',
            ], 422);
        }

        $otpValidMinutes = (int) config('sensitive_access.otp_valid_minutes', 5);
        $otpCode = (string) random_int(100000, 999999);
        $channels = [];

        if (filled($user->email)) {
            $channels[] = 'email';
        }

        if (filled($user->phone)) {
            $channels[] = 'mobile';
        }

        $log = OtpVerificationLog::query()->create([
            'user_id' => $user->id,
            'subject_type' => $data['subject_type'],
            'subject_id' => (int) $data['subject_id'],
            'email' => $user->email,
            'phone' => $user->phone,
            'delivery_channels' => $channels,
            'otp_code_hash' => Hash::make($otpCode),
            'requested_at' => now(),
            'expires_at' => now()->addMinutes($otpValidMinutes),
            'status' => 'sent',
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'metadata' => [
                'subject_label' => $subject['label'],
                'redirect_url' => $data['redirect_url'],
                'access_action' => $accessAction,
                'sms_delivery' => filled($user->phone) ? 'gateway_not_configured' : 'no_mobile',
            ],
        ]);

        $this->activityLogService->log(
            $request,
            'sensitive_access',
            'otp_requested',
            $subject['model'],
            $user,
            'Requested sensitive access OTP.',
            [
                'access_action' => $accessAction,
                'subject_id' => (int) $data['subject_id'],
                'otp_log_id' => $log->id,
            ]
        );

        if (filled($user->email)) {
            try {
                Notification::route('mail', $user->email)
                    ->notify(new SensitiveAccessOtpNotification($otpCode, $subject['label'], $otpValidMinutes));
            } catch (Throwable $throwable) {
                report($throwable);
                $log->update([
                    'status' => 'failed',
                    'metadata' => array_merge($log->metadata ?? [], [
                        'email_error' => $throwable->getMessage(),
                    ]),
                ]);

                return response()->json([
                    'message' => 'Failed to deliver OTP email. Please try again shortly.',
                ], 500);
            }
        }

        return response()->json([
            'message' => 'OTP sent successfully to your registered email/mobile.',
            'log_id' => $log->id,
            'valid_minutes' => $otpValidMinutes,
        ]);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subject_type' => 'required|string|in:enrollment',
            'subject_id' => 'required|integer|min:1',
            'otp' => 'required|digits:6',
            'redirect_url' => 'required|url',
            'access_action' => 'nullable|string|in:view,edit,documents',
        ]);

        /** @var User|null $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (!$this->requiresSensitiveOtp($user)) {
            return response()->json([
                'message' => 'OTP verification is not required for this user.',
                'redirect_url' => $data['redirect_url'],
            ]);
        }

        $accessAction = (string) ($data['access_action'] ?? 'view');
        $subject = $this->findSubject($data['subject_type'], (int) $data['subject_id']);

        if (!$subject) {
            return response()->json(['message' => 'Unable to find requested record.'], 404);
        }

        $this->recordAccess->assertCanAccessRecord($user, $subject['model']);

        $maxAttempts = (int) config('sensitive_access.max_attempts', 5);

        $otpLog = OtpVerificationLog::query()
            ->where('user_id', $user->id)
            ->where('subject_type', $data['subject_type'])
            ->where('subject_id', (int) $data['subject_id'])
            ->whereIn('status', ['sent'])
            ->latest('id')
            ->first();

        if (!$otpLog) {
            return response()->json(['message' => 'OTP request not found. Please request OTP again.'], 422);
        }

        if (now()->greaterThan($otpLog->expires_at)) {
            $otpLog->update([
                'status' => 'expired',
                'last_attempt_at' => now(),
            ]);

            return response()->json(['message' => 'OTP expired. Please request a new OTP.'], 422);
        }

        if (!Hash::check($data['otp'], $otpLog->otp_code_hash)) {
            $attempts = $otpLog->failed_attempts + 1;
            $status = $attempts >= $maxAttempts ? 'failed' : 'sent';

            $otpLog->update([
                'failed_attempts' => $attempts,
                'status' => $status,
                'last_attempt_at' => now(),
            ]);

            $this->activityLogService->log(
                $request,
                'sensitive_access',
                'otp_failed',
                $subject['model'],
                $user,
                'Sensitive access OTP verification failed.',
                [
                    'access_action' => $accessAction,
                    'attempts' => $attempts,
                    'otp_log_id' => $otpLog->id,
                ]
            );

            if ($status === 'failed') {
                return response()->json(['message' => 'Maximum OTP attempts reached. Please request a new OTP.'], 422);
            }

            return response()->json(['message' => 'Invalid OTP. Please try again.'], 422);
        }

        $sessionValidMinutes = (int) config('sensitive_access.session_valid_minutes', 5);

        $otpLog->update([
            'status' => 'verified',
            'verified_at' => now(),
            'last_attempt_at' => now(),
            'metadata' => array_merge($otpLog->metadata ?? [], [
                'access_action' => $accessAction,
            ]),
        ]);

        $sessionKey = $this->sessionKey($data['subject_type'], (int) $data['subject_id'], $accessAction);
        $request->session()->put($sessionKey, now()->addMinutes($sessionValidMinutes)->toIso8601String());

        $this->activityLogService->log(
            $request,
            'sensitive_access',
            'otp_verified',
            $subject['model'],
            $user,
            'Sensitive access OTP verified.',
            [
                'access_action' => $accessAction,
                'otp_log_id' => $otpLog->id,
                'session_valid_minutes' => $sessionValidMinutes,
            ]
        );

        return response()->json([
            'message' => 'OTP verified successfully.',
            'redirect_url' => $data['redirect_url'],
            'session_valid_minutes' => $sessionValidMinutes,
        ]);
    }

    private function requiresSensitiveOtp(User $user): bool
    {
        return !in_array($user->role, ['super_admin', 'admin'], true);
    }

    private function findSubject(string $subjectType, int $subjectId): ?array
    {
        if ($subjectType !== 'enrollment') {
            return null;
        }

        $enrollment = Enrollment::query()->find($subjectId);
        if (!$enrollment) {
            return null;
        }

        return [
            'id' => $enrollment->id,
            'label' => trim(($enrollment->doctor_name ?: 'Doctor') . ' / ' . ($enrollment->customer_id_no ?: 'No Membership No')),
            'model' => $enrollment,
        ];
    }

    private function sessionKey(string $subjectType, int $subjectId, string $accessAction): string
    {
        return 'sensitive_otp.' . $subjectType . '.' . $subjectId . '.' . $accessAction;
    }

    private function shouldBypassSensitiveOtp(User $user, Enrollment $enrollment, string $accessAction): bool
    {
        if (!$this->requiresSensitiveOtp($user)) {
            return true;
        }

        if (!$this->recordAccess->ownsRecord($user, $enrollment)) {
            return false;
        }

        if (EnrollmentWorkflow::canContinueDraftEntry($enrollment)) {
            return true;
        }

        if ($accessAction === 'edit') {
            return !$this->editAccess->requiresOtpGuardForUser($enrollment, $user);
        }

        return false;
    }
}

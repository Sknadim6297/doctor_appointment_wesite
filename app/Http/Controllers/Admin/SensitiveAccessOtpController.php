<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\OtpVerificationLog;
use App\Models\User;
use App\Notifications\SensitiveAccessOtpNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Throwable;

class SensitiveAccessOtpController extends Controller
{
    private const OTP_VALID_MINUTES = 10;
    private const SESSION_VALID_MINUTES = 20;
    private const MAX_ATTEMPTS = 5;

    public function requestOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subject_type' => 'required|string|in:enrollment',
            'subject_id' => 'required|integer|min:1',
            'redirect_url' => 'required|url',
        ]);

        /** @var User|null $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (!$this->requiresSensitiveOtp($user)) {
            return response()->json(['message' => 'OTP verification is not required for this user.']);
        }

        $subject = $this->findSubject($data['subject_type'], (int) $data['subject_id']);
        if (!$subject) {
            return response()->json(['message' => 'Unable to find requested record.'], 404);
        }

        if (blank($user->email) && blank($user->phone)) {
            return response()->json([
                'message' => 'No registered email or mobile is available for OTP delivery.',
            ], 422);
        }

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
            'expires_at' => now()->addMinutes(self::OTP_VALID_MINUTES),
            'status' => 'sent',
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'metadata' => [
                'subject_label' => $subject['label'],
                'redirect_url' => $data['redirect_url'],
                'sms_delivery' => filled($user->phone) ? 'gateway_not_configured' : 'no_mobile',
            ],
        ]);

        if (filled($user->email)) {
            try {
                Notification::route('mail', $user->email)
                    ->notify(new SensitiveAccessOtpNotification($otpCode, $subject['label'], self::OTP_VALID_MINUTES));
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
            'valid_minutes' => self::OTP_VALID_MINUTES,
        ]);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subject_type' => 'required|string|in:enrollment',
            'subject_id' => 'required|integer|min:1',
            'otp' => 'required|digits:6',
            'redirect_url' => 'required|url',
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
            $status = $attempts >= self::MAX_ATTEMPTS ? 'failed' : 'sent';

            $otpLog->update([
                'failed_attempts' => $attempts,
                'status' => $status,
                'last_attempt_at' => now(),
            ]);

            if ($status === 'failed') {
                return response()->json(['message' => 'Maximum OTP attempts reached. Please request a new OTP.'], 422);
            }

            return response()->json(['message' => 'Invalid OTP. Please try again.'], 422);
        }

        $otpLog->update([
            'status' => 'verified',
            'verified_at' => now(),
            'last_attempt_at' => now(),
        ]);

        $sessionKey = $this->sessionKey($data['subject_type'], (int) $data['subject_id']);
        $request->session()->put($sessionKey, now()->addMinutes(self::SESSION_VALID_MINUTES)->toIso8601String());

        return response()->json([
            'message' => 'OTP verified successfully.',
            'redirect_url' => $data['redirect_url'],
            'session_valid_minutes' => self::SESSION_VALID_MINUTES,
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
        ];
    }

    private function sessionKey(string $subjectType, int $subjectId): string
    {
        return 'sensitive_otp.' . $subjectType . '.' . $subjectId;
    }
}

<?php

namespace App\Services;

use App\Models\AdminSecurityNotification;
use App\Models\Enrollment;
use App\Models\User;
use App\Notifications\AdminSecurityAlertNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class SecurityAlertService
{
    public function __construct(private readonly ClientContextService $clientContextService)
    {
    }

    public function notifySensitiveEnrollmentAccess(Request $request, Enrollment $enrollment, User $owner): ?AdminSecurityNotification
    {
        $actor = Auth::user();

        if (!$actor || (int) $actor->id === (int) $owner->id) {
            return null;
        }

        $context = $this->clientContextService->fromRequest($request);
        $notification = AdminSecurityNotification::query()->create([
            'owner_user_id' => $owner->id,
            'actor_user_id' => $actor->id,
            'subject_type' => Enrollment::class,
            'subject_id' => $enrollment->id,
            'module_key' => 'doctors',
            'action' => 'view',
            'email' => $owner->email,
            'otp_code' => (string) random_int(100000, 999999),
            'otp_expires_at' => now()->addMinutes(10),
            'notified_at' => now(),
            'ip_address' => $context['ip_address'],
            'device_name' => $context['device_name'],
            'browser_name' => trim(($context['browser_name'] ?? 'Unknown') . ' ' . ($context['browser_version'] ?? '')),
            'metadata' => [
                'doctor_name' => $enrollment->doctor_name,
                'membership_no' => $enrollment->customer_id_no,
            ],
        ]);

        try {
            $owner->notify(new AdminSecurityAlertNotification(
                $notification,
                $actor->name,
                trim(($enrollment->doctor_name ?: 'Doctor') . ' / ' . ($enrollment->customer_id_no ?: 'No Membership No'))
            ));
        } catch (Throwable $throwable) {
            report($throwable);
        }

        return $notification;
    }
}
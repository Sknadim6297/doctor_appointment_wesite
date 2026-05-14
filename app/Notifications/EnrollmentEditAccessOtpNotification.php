<?php

namespace App\Notifications;

use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EnrollmentEditAccessOtpNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $otpCode,
        private readonly Enrollment $enrollment,
        private readonly User $requester,
        private readonly int $validMinutes
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $doctor = $this->enrollment->doctor_name ?: 'Enrollment #' . $this->enrollment->id;
        $requesterName = $this->requester->name ?? 'Staff';

        return (new MailMessage)
            ->subject('Enrollment edit access — OTP verification required')
            ->greeting('Hello ' . ($notifiable->first_name ?? $notifiable->name ?? 'Administrator') . ',')
            ->line($requesterName . ' requested temporary edit access for enrollment: ' . $doctor . ' (Customer ID: ' . ($this->enrollment->customer_id_no ?: '—') . ').')
            ->line('Use this one-time OTP in the admin console to approve the request (Enrollment details → Verify OTP):')
            ->line('OTP: ' . $this->otpCode)
            ->line('This OTP expires in ' . $this->validMinutes . ' minutes and can be used only once.')
            ->line('After verification, the requester receives a **' . config('enrollment_edit_access.edit_session_minutes', 60) . '-minute** edit window.')
            ->line('If you did not expect this request, do not share the OTP and review account activity.');
    }
}

<?php

namespace App\Notifications;

use App\Models\AdminSecurityNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminSecurityAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly AdminSecurityNotification $securityNotification,
        private readonly string $actorName,
        private readonly string $subjectLabel
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $otpExpiresAt = optional($this->securityNotification->otp_expires_at)->format('d/m/Y h:i A');

        return (new MailMessage)
            ->subject('Security Alert: Sensitive Data Access Detected')
            ->greeting('Hello ' . ($notifiable->first_name ?: $notifiable->name) . ',')
            ->line('A sensitive data access event has been detected for data created by your account.')
            ->line('Accessed by: ' . $this->actorName)
            ->line('Action: ' . ucfirst(str_replace('_', ' ', $this->securityNotification->action)))
            ->line('Record: ' . $this->subjectLabel)
            ->line('Device: ' . ($this->securityNotification->device_name ?: '-'))
            ->line('Browser: ' . ($this->securityNotification->browser_name ?: '-'))
            ->line('IP Address: ' . ($this->securityNotification->ip_address ?: '-'))
            ->line('Verification OTP: ' . $this->securityNotification->otp_code)
            ->line('OTP valid until: ' . ($otpExpiresAt ?: '-'))
            ->line('Review this activity immediately if it was not expected.');
    }
}
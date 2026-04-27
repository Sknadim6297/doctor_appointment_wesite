<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SensitiveAccessOtpNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $otpCode,
        private readonly string $subjectLabel,
        private readonly int $validMinutes
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('OTP Verification for Sensitive Details')
            ->greeting('Hello ' . ($notifiable->first_name ?: $notifiable->name) . ',')
            ->line('You requested access to sensitive information: ' . $this->subjectLabel)
            ->line('Your OTP is: ' . $this->otpCode)
            ->line('This OTP is valid for ' . $this->validMinutes . ' minutes.')
            ->line('If you did not request this access, please contact your administrator immediately.');
    }
}

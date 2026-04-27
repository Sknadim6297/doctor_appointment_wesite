<?php

namespace App\Notifications;

use App\Models\Enrollment;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminExpiryReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Enrollment $doctor,
        private readonly CarbonInterface $expiryDate
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $status = 'Due in 25 days';

        return (new MailMessage)
            ->subject('Admin Alert: Doctor Expiry Reminder (25 Days)')
            ->greeting('Hello Admin,')
            ->line('A doctor account is approaching expiry and requires follow-up.')
            ->line('Doctor name: ' . ($this->doctor->doctor_name ?: 'N/A'))
            ->line('Membership no: ' . ($this->doctor->customer_id_no ?: 'N/A'))
            ->line('Expiry date: ' . $this->expiryDate->format('d/m/Y'))
            ->line('Doctor email: ' . ($this->doctor->doctor_email ?: 'N/A'))
            ->line('Doctor phone: ' . (($this->doctor->mobile1 ?: '') ?: 'N/A'))
            ->line('Status: ' . $status)
            ->line('Please ensure renewal follow-up is completed on time.');
    }
}

<?php

namespace App\Notifications;

use App\Models\Enrollment;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DoctorExpiryReminderNotification extends Notification
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
        $doctorName = $this->doctor->doctor_name ?: 'Doctor';

        return (new MailMessage)
            ->subject('Medeforum Renewal Reminder - 40 Days Remaining')
            ->greeting('Dear Dr. ' . $doctorName . ',')
            ->line('This is an automated reminder that your Medeforum membership/subscription is due to expire in 40 days.')
            ->line('Expiry date: ' . $this->expiryDate->format('d/m/Y'))
            ->line('Membership no: ' . ($this->doctor->customer_id_no ?: 'N/A'))
            ->line('Renewal instructions:')
            ->line('1) Log in to your Medeforum portal and review your profile details.')
            ->line('2) Complete renewal payment and required document updates.')
            ->line('3) Contact our support team if you need assistance with renewal.')
            ->line('Please complete renewal before expiry to avoid service interruption.');
    }
}

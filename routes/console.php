<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\ExpiryReminderService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('reminders:send-expiry', function (ExpiryReminderService $expiryReminderService) {
    $stats = $expiryReminderService->sendDueReminders();

    $this->info('Expiry reminder job completed.');
    $this->table(['Metric', 'Count'], [
        ['Doctor reminders sent', $stats['doctor_sent']],
        ['Doctor reminders skipped', $stats['doctor_skipped']],
        ['Doctor reminders failed', $stats['doctor_failed']],
        ['Admin reminders sent', $stats['admin_sent']],
        ['Admin reminders skipped', $stats['admin_skipped']],
        ['Admin reminders failed', $stats['admin_failed']],
    ]);
})->purpose('Send doctor and admin reminder emails before expiry dates');

Schedule::command('reminders:send-expiry')->dailyAt('09:00');

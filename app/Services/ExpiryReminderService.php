<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\ExpiryReminderLog;
use App\Models\User;
use App\Notifications\AdminExpiryReminderNotification;
use App\Notifications\DoctorExpiryReminderNotification;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Throwable;

class ExpiryReminderService
{
    /**
     * @return array{doctor_sent:int,doctor_skipped:int,doctor_failed:int,admin_sent:int,admin_skipped:int,admin_failed:int}
     */
    public function sendDueReminders(?CarbonImmutable $today = null): array
    {
        $today = $today ?: CarbonImmutable::today();

        $stats = [
            'doctor_sent' => 0,
            'doctor_skipped' => 0,
            'doctor_failed' => 0,
            'admin_sent' => 0,
            'admin_skipped' => 0,
            'admin_failed' => 0,
        ];

        $this->sendDoctorReminders($today, $stats);
        $this->sendAdminReminders($today, $stats);

        return $stats;
    }

    private function sendDoctorReminders(CarbonImmutable $today, array &$stats): void
    {
        $targetDate = $today->addDays(40)->toDateString();

        Enrollment::query()
            ->whereDate(DB::raw('DATE_ADD(created_at, INTERVAL 1 YEAR)'), $targetDate)
            ->orderBy('id')
            ->chunkById(200, function ($doctors) use ($targetDate, &$stats): void {
                foreach ($doctors as $doctor) {
                    $recipientEmail = $doctor->doctor_email ?: '__missing_doctor_email__';
                    $expiryDate = optional($doctor->created_at)->copy()?->addYear()?->toDateString();

                    if (!$expiryDate) {
                        $stats['doctor_skipped']++;
                        continue;
                    }

                    $log = ExpiryReminderLog::query()->firstOrCreate(
                        [
                            'reminder_type' => 'doctor_40',
                            'enrollment_id' => $doctor->id,
                            'recipient_email' => $recipientEmail,
                            'expiry_date' => $expiryDate,
                        ],
                        [
                            'doctor_name' => $doctor->doctor_name,
                            'days_before_expiry' => 40,
                            'status' => 'pending',
                            'metadata' => [
                                'membership_no' => $doctor->customer_id_no,
                                'phone' => $doctor->mobile1,
                            ],
                        ]
                    );

                    if (!$log->wasRecentlyCreated) {
                        $stats['doctor_skipped']++;
                        continue;
                    }

                    if (blank($doctor->doctor_email)) {
                        $log->update([
                            'status' => 'skipped',
                            'error_message' => 'Doctor email not available.',
                        ]);
                        $stats['doctor_skipped']++;
                        continue;
                    }

                    try {
                        Notification::route('mail', $doctor->doctor_email)
                            ->notify(new DoctorExpiryReminderNotification($doctor, CarbonImmutable::parse($expiryDate)));

                        $log->update([
                            'status' => 'sent',
                            'sent_at' => now(),
                            'error_message' => null,
                        ]);
                        $stats['doctor_sent']++;
                    } catch (Throwable $throwable) {
                        report($throwable);
                        $log->update([
                            'status' => 'failed',
                            'error_message' => $throwable->getMessage(),
                        ]);
                        $stats['doctor_failed']++;
                    }
                }
            });
    }

    private function sendAdminReminders(CarbonImmutable $today, array &$stats): void
    {
        $targetDate = $today->addDays(25)->toDateString();

        $adminEmails = User::query()
            ->where('is_active', true)
            ->whereIn('role', ['super_admin', 'admin'])
            ->whereNotNull('email')
            ->pluck('email')
            ->filter()
            ->unique()
            ->values();

        if ($adminEmails->isEmpty()) {
            return;
        }

        Enrollment::query()
            ->whereDate(DB::raw('DATE_ADD(created_at, INTERVAL 1 YEAR)'), $targetDate)
            ->orderBy('id')
            ->chunkById(200, function ($doctors) use ($adminEmails, &$stats): void {
                foreach ($doctors as $doctor) {
                    $expiryDate = optional($doctor->created_at)->copy()?->addYear()?->toDateString();

                    if (!$expiryDate) {
                        $stats['admin_skipped']++;
                        continue;
                    }

                    foreach ($adminEmails as $adminEmail) {
                        $log = ExpiryReminderLog::query()->firstOrCreate(
                            [
                                'reminder_type' => 'admin_25',
                                'enrollment_id' => $doctor->id,
                                'recipient_email' => $adminEmail,
                                'expiry_date' => $expiryDate,
                            ],
                            [
                                'doctor_name' => $doctor->doctor_name,
                                'days_before_expiry' => 25,
                                'status' => 'pending',
                                'metadata' => [
                                    'membership_no' => $doctor->customer_id_no,
                                    'phone' => $doctor->mobile1,
                                    'doctor_email' => $doctor->doctor_email,
                                    'status' => 'due_in_25_days',
                                ],
                            ]
                        );

                        if (!$log->wasRecentlyCreated) {
                            $stats['admin_skipped']++;
                            continue;
                        }

                        try {
                            Notification::route('mail', $adminEmail)
                                ->notify(new AdminExpiryReminderNotification($doctor, CarbonImmutable::parse($expiryDate)));

                            $log->update([
                                'status' => 'sent',
                                'sent_at' => now(),
                                'error_message' => null,
                            ]);
                            $stats['admin_sent']++;
                        } catch (Throwable $throwable) {
                            report($throwable);
                            $log->update([
                                'status' => 'failed',
                                'error_message' => $throwable->getMessage(),
                            ]);
                            $stats['admin_failed']++;
                        }
                    }
                }
            });
    }
}

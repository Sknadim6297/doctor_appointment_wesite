<?php

namespace App\Console\Commands;

use App\Models\AdminActivityLog;
use App\Models\Enrollment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class PurgeEnrollmentDataCommand extends Command
{
    protected $signature = 'enrollment:purge
                            {--force : Skip confirmation prompt}';

    protected $description = 'Remove all enrollments, policy receipts, and related workflow data (fresh start).';

    public function handle(): int
    {
        if (!$this->option('force') && !$this->confirm('Delete ALL enrollments, policy receipts, documents, and posts? This cannot be undone.')) {
            $this->info('Cancelled.');

            return self::SUCCESS;
        }

        $enrollmentCount = Enrollment::query()->count();
        $policyCount = DB::table('policy_receipts')->count();

        $this->info("Found {$enrollmentCount} enrollment(s) and {$policyCount} policy receipt(s).");

        // TRUNCATE implicitly commits in MySQL — do not wrap in a DB transaction.
        Schema::disableForeignKeyConstraints();

        $this->purgeStorage();

        $tables = [
            'enrollment_edit_access_sessions',
            'doctor_documents',
            'policy_receipts',
            'doctor_posts',
            'expiry_reminder_logs',
            'renewal_cheque_deposits',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
                $this->line("Truncated {$table}");
            }
        }

        if (Schema::hasTable('legal_cases')) {
            DB::table('legal_cases')->whereNotNull('enrollment_id')->delete();
            $this->line('Deleted legal_cases linked to enrollments');
        }

        AdminActivityLog::query()
            ->where('subject_type', Enrollment::class)
            ->delete();

        $this->line('Deleted enrollment activity logs');

        if (Schema::hasTable('enrollments')) {
            DB::table('enrollments')->truncate();
            $this->line('Truncated enrollments');
        }

        Schema::enableForeignKeyConstraints();

        $this->newLine();
        $this->info('Enrollment pipeline data cleared. You can create new enrollments from Enrollment Entry.');

        return self::SUCCESS;
    }

    private function purgeStorage(): void
    {
        foreach (['policy_files', 'doctor_documents', 'doctor_posts', 'enrollment-pdfs'] as $directory) {
            if (Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->deleteDirectory($directory);
                Storage::disk('public')->makeDirectory($directory);
                $this->line("Cleared storage/{$directory}");
            }
        }
    }
}

<?php

namespace App\Console\Commands;

use App\Services\LegacyRenewalHistoryImportService;
use Illuminate\Console\Command;

class ImportLegacyRenewalHistoryCommand extends Command
{
    protected $signature = 'legacy:import-renewal-history
                            {--file= : Path to legacy_tbl_renew_history SQL dump}
                            {--load-only : Only load SQL into staging table}
                            {--sync-only : Only sync from staging to renewal_histories and enrollments}
                            {--dry-run : Preview without writing}
                            {--no-truncate : Do not truncate staging before load}
                            {--keep-histories : Do not truncate renewal_histories before sync}';

    protected $description = 'Import legacy tbl_renew_history and link renewals to enrollments';

    public function handle(LegacyRenewalHistoryImportService $service): int
    {
        $file = $this->resolveFilePath();

        if ($file === null) {
            $this->error('SQL file not found. Use --file= or place dump at storage/app/legacy_tbl_renew_history.sql');

            return self::FAILURE;
        }

        $loadOnly = (bool) $this->option('load-only');
        $syncOnly = (bool) $this->option('sync-only');
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Dry run enabled — no data will be written.');
        }

        if (!$syncOnly) {
            $this->info("Loading SQL: {$file}");
            $loaded = $service->loadSqlFile($file, !$this->option('no-truncate'));
            $this->info("Staging rows loaded (approx INSERT batches): {$loaded}");
            $this->line('Staging row count: ' . $service->stagingRowCount());
        }

        if (!$loadOnly) {
            $this->info('Syncing renewal history and updating enrollments…');
            $stats = $service->syncToApp($dryRun, !$this->option('keep-histories'));

            $this->table(
                ['Metric', 'Count'],
                [
                    ['renewal_histories rows', $stats['histories']],
                    ['enrollments updated (last/next renewal)', $stats['enrollments_updated']],
                    ['policy_receipts updated', $stats['policies_updated']],
                    ['skipped (no enrollment)', $stats['skipped']],
                    ['errors', count($stats['errors'])],
                ]
            );

            if ($stats['errors'] !== []) {
                $this->newLine();
                $this->warn('Sample errors:');
                foreach (array_slice($stats['errors'], 0, 10, true) as $doctorId => $message) {
                    $this->line("Doctor {$doctorId}: {$message}");
                }
            }
        }

        $this->newLine();
        $this->info('Done.');

        return self::SUCCESS;
    }

    private function resolveFilePath(): ?string
    {
        $file = $this->option('file');

        if (is_string($file) && $file !== '') {
            $path = $file;

            if (is_file($path)) {
                return $path;
            }

            $base = base_path();
            $candidate = $path;

            if (!str_starts_with($candidate, DIRECTORY_SEPARATOR) && !preg_match('/^[A-Za-z]:/', $candidate)) {
                $candidate = $base . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
            }

            return is_file($candidate) ? $candidate : null;
        }

        $candidates = [
            storage_path('app/legacy_tbl_renew_history.sql'),
            storage_path('app/import/legacy_tbl_renew_history.sql'),
            base_path('legacy_tbl_renew_history.sql'),
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}

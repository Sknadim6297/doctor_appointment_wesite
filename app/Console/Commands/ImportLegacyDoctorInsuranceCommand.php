<?php

namespace App\Console\Commands;

use App\Services\LegacyDoctorInsuranceImportService;
use Illuminate\Console\Command;

class ImportLegacyDoctorInsuranceCommand extends Command
{
    protected $signature = 'legacy:import-doctor-insurance
                            {--file=storage/app/legacy_tbl_doctor_insurance.sql : Path to legacy tbl_doctor_insurance SQL dump}
                            {--sync-only : Sync from staging without loading SQL}
                            {--dry-run : Preview counts without writing}
                            {--no-truncate : Append to staging instead of truncating before load}';

    protected $description = 'Import plan, coverage, and premium amounts from legacy tbl_doctor_insurance into enrollments.';

    public function handle(LegacyDoctorInsuranceImportService $importService): int
    {
        $syncOnly = (bool) $this->option('sync-only');
        $file = (string) $this->option('file');

        if (!$syncOnly) {
            if (!is_file($file)) {
                $this->error("SQL file not found: {$file}");

                return self::FAILURE;
            }

            $this->info("Loading SQL from {$file}...");

            try {
                $loaded = $importService->loadSqlFile($file, !$this->option('no-truncate'));
            } catch (\Throwable $exception) {
                $this->error($exception->getMessage());

                return self::FAILURE;
            }

            $this->info("Executed INSERT statements (~{$loaded} row value groups).");
        }

        $stagingCount = $importService->stagingRowCount();

        if ($stagingCount === 0) {
            $this->warn('No rows in legacy_tbl_doctor_insurance staging table.');

            return self::FAILURE;
        }

        $this->info("Staging rows: {$stagingCount}");

        try {
            $stats = $importService->syncToEnrollments((bool) $this->option('dry-run'));
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->line('Dry run — no changes written.');
        }

        $this->table(
            ['Metric', 'Count'],
            [
                ['Enrollments updated', $stats['updated']],
                ['Skipped', $stats['skipped']],
                ['Errors', count($stats['errors'])],
            ]
        );

        if ($stats['errors'] !== []) {
            foreach (array_slice($stats['errors'], 0, 10, true) as $legacyUserId => $message) {
                $this->warn("Doctor {$legacyUserId}: {$message}");
            }
        }

        $this->info('Doctor list speciality, plan, coverage, and amounts are populated from this data.');

        return self::SUCCESS;
    }
}

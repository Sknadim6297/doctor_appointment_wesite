<?php

namespace App\Console\Commands;

use App\Services\LegacyDoctorDetailsImportService;
use Illuminate\Console\Command;

class ImportLegacyDoctorDetailsCommand extends Command
{
    protected $signature = 'legacy:import-doctor-details
                            {--file= : Path to legacy tbl_doctor_details SQL dump}
                            {--sync-only : Sync from staging without loading SQL}
                            {--dry-run : Preview update counts without writing enrollments}
                            {--no-truncate : Append to staging instead of truncating before load}';

    protected $description = 'Load legacy tbl_doctor_details and merge qualification, clinic, agent, and specialization into enrollments.';

    public function handle(LegacyDoctorDetailsImportService $importService): int
    {
        $file = $this->option('file');
        $syncOnly = (bool) $this->option('sync-only');
        $dryRun = (bool) $this->option('dry-run');

        if (!$syncOnly) {
            if (!$file) {
                $default = storage_path('app/legacy_tbl_doctor_details.sql');

                if (is_file($default)) {
                    $file = $default;
                    $this->comment("Using default file: {$file}");
                } else {
                    $this->error('Provide --file=path/to/dump.sql or use --sync-only after loading staging.');

                    return self::FAILURE;
                }
            }

            $this->info("Loading SQL from {$file}...");

            try {
                $loaded = $importService->loadSqlFile(
                    $file,
                    !$this->option('no-truncate')
                );
            } catch (\Throwable $exception) {
                $this->error($exception->getMessage());

                return self::FAILURE;
            }

            $this->info("Executed INSERT statements (~{$loaded} row value groups).");
        }

        $stagingCount = $importService->stagingRowCount();

        if ($stagingCount === 0) {
            $this->warn('No rows in legacy_tbl_doctor_details staging table.');

            return self::FAILURE;
        }

        $this->info("Staging doctor details: {$stagingCount}");

        if ($dryRun) {
            $this->comment('Dry run — no enrollment writes.');
        }

        $stats = $importService->syncDetailsToEnrollments($dryRun);

        $this->table(
            ['Metric', 'Count'],
            [
                ['Enrollments updated', $stats['updated']],
                ['Skipped (no matching enrollment / empty row)', $stats['skipped']],
                ['Errors', count($stats['errors'])],
            ]
        );

        if ($stats['errors'] !== []) {
            $this->warn('Import errors (sample):');

            foreach (array_slice($stats['errors'], 0, 10, true) as $legacyDetailsId => $message) {
                $this->line("  doctor_detais_id {$legacyDetailsId}: {$message}");
            }

            return self::FAILURE;
        }

        $this->info($dryRun ? 'Dry run complete.' : 'Legacy doctor details import complete.');

        return self::SUCCESS;
    }
}

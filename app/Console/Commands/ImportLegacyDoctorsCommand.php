<?php

namespace App\Console\Commands;

use App\Services\LegacyDoctorDetailsImportService;
use App\Services\LegacyDoctorImportService;
use App\Services\LegacyStaffImportService;
use Illuminate\Console\Command;

class ImportLegacyDoctorsCommand extends Command
{
    protected $signature = 'legacy:import-doctors
                            {--file= : Path to legacy tbl_user SQL dump (INSERT statements)}
                            {--sync-only : Skip loading SQL; sync from legacy_tbl_user staging only}
                            {--dry-run : Preview create/update counts without writing enrollments}
                            {--only-active : Import only rows with status=active}
                            {--no-truncate : Append to staging instead of truncating before load}
                            {--skip-staff : Do not import staff/admin rows into users}
                            {--skip-doctors : Do not sync doctor rows into enrollments}
                            {--with-details : After doctor sync, merge tbl_doctor_details from storage/app/legacy_tbl_doctor_details.sql}
                            {--details-file= : Override SQL path for --with-details}';

    protected $description = 'Load legacy tbl_user dump, import staff into users, doctors into enrollments.';

    public function handle(
        LegacyDoctorImportService $importService,
        LegacyStaffImportService $staffImportService,
        LegacyDoctorDetailsImportService $doctorDetailsImportService
    ): int
    {
        $file = $this->option('file');
        $syncOnly = (bool) $this->option('sync-only');
        $dryRun = (bool) $this->option('dry-run');
        $onlyActive = (bool) $this->option('only-active');

        if (!$syncOnly) {
            if (!$file) {
                $this->error('Provide --file=path/to/dump.sql or use --sync-only after loading staging.');

                return self::FAILURE;
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

        $hasErrors = false;

        if (!$this->option('skip-staff')) {
            $staffCount = $staffImportService->stagingStaffCount();

            if ($staffCount === 0) {
                $this->warn('No staff rows (user_type_id 1–2) in staging.');
            } else {
                $this->info("Staging staff/admin: {$staffCount}");

                $staffStats = $staffImportService->syncStaffFromStaging($dryRun);

                $this->table(
                    ['Staff metric', 'Count'],
                    [
                        ['Linked (super admin)', $staffStats['linked']],
                        ['Created', $staffStats['created']],
                        ['Updated', $staffStats['updated']],
                        ['Skipped', $staffStats['skipped']],
                        ['Errors', count($staffStats['errors'])],
                    ]
                );

                if ($staffStats['errors'] !== []) {
                    $hasErrors = true;
                    $this->warn('Staff import errors (sample):');

                    foreach (array_slice($staffStats['errors'], 0, 5, true) as $legacyId => $message) {
                        $this->line("  legacy id {$legacyId}: {$message}");
                    }
                }
            }

            $this->newLine();
        }

        if (!$this->option('skip-doctors')) {
            $stagingCount = $importService->stagingRowCount();

            if ($stagingCount === 0) {
                $this->warn('No doctor rows (user_type_id=3) in legacy_tbl_user staging table.');

                return self::FAILURE;
            }

            $this->info("Staging doctors: {$stagingCount}");

            if ($dryRun) {
                $this->comment('Dry run — no enrollment writes.');
            }

            if ($onlyActive) {
                $this->comment('Filter: status=active only.');
            }

            $stats = $importService->syncDoctorsFromStaging($dryRun, $onlyActive);

            $this->table(
                ['Doctor metric', 'Count'],
                [
                    ['Created', $stats['created']],
                    ['Updated', $stats['updated']],
                    ['Skipped', $stats['skipped']],
                    ['Errors', count($stats['errors'])],
                ]
            );

            if ($stats['errors'] !== []) {
                $hasErrors = true;
                $this->warn('Doctor import errors (sample):');

                foreach (array_slice($stats['errors'], 0, 10, true) as $legacyId => $message) {
                    $this->line("  legacy id {$legacyId}: {$message}");
                }
            }
        }

        if ($this->option('with-details') && !$this->option('skip-doctors')) {
            $detailsFile = $this->option('details-file') ?: storage_path('app/legacy_tbl_doctor_details.sql');

            if (!is_file($detailsFile)) {
                $this->error("Doctor details SQL not found: {$detailsFile}");
                $hasErrors = true;
            } else {
                $this->newLine();
                $this->info("Loading doctor details from {$detailsFile}...");

                try {
                    $loaded = $doctorDetailsImportService->loadSqlFile($detailsFile);
                    $this->info("Doctor details INSERT groups: ~{$loaded}");
                } catch (\Throwable $exception) {
                    $this->error($exception->getMessage());
                    $hasErrors = true;
                }

                if (!$hasErrors) {
                    $detailStats = $doctorDetailsImportService->syncDetailsToEnrollments($dryRun);

                    $this->table(
                        ['Doctor details', 'Count'],
                        [
                            ['Enrollments updated', $detailStats['updated']],
                            ['Skipped', $detailStats['skipped']],
                            ['Errors', count($detailStats['errors'])],
                        ]
                    );

                    if ($detailStats['errors'] !== []) {
                        $hasErrors = true;
                    }
                }
            }
        }

        $this->newLine();
        $this->info($dryRun ? 'Dry run complete.' : 'Legacy user import complete.');

        return $hasErrors ? self::FAILURE : self::SUCCESS;
    }
}

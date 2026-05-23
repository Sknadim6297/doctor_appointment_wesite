<?php

namespace App\Console\Commands;

use App\Services\LegacyDoctorPolicyImportService;
use Illuminate\Console\Command;

class ImportLegacyDoctorPoliciesCommand extends Command
{
    protected $signature = 'legacy:import-doctor-policies
                            {--file= : Path to legacy tbl_doctor_policy SQL dump}
                            {--sync-only : Sync from staging without loading SQL}
                            {--dry-run : Preview counts without writing policy_receipts}
                            {--no-truncate : Append to staging instead of truncating before load}';

    protected $description = 'Load legacy tbl_doctor_policy and sync policy numbers into policy_receipts linked by doctor_id → enrollments.legacy_user_id.';

    public function handle(LegacyDoctorPolicyImportService $importService): int
    {
        $file = $this->option('file');
        $syncOnly = (bool) $this->option('sync-only');
        $dryRun = (bool) $this->option('dry-run');

        if (!$syncOnly) {
            if (!$file) {
                $default = storage_path('app/legacy_tbl_doctor_policy.sql');

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
            $this->warn('No rows in legacy_tbl_doctor_policy staging table.');

            return self::FAILURE;
        }

        $this->info("Staging doctor policies: {$stagingCount}");

        if ($dryRun) {
            $this->comment('Dry run — no policy_receipts writes.');
        }

        $stats = $importService->syncFromStaging($dryRun);

        $this->table(
            ['Metric', 'Count'],
            [
                ['Created', $stats['created']],
                ['Updated', $stats['updated']],
                ['Linked to enrollment', $stats['linked']],
                ['Skipped (invalid policy_id / empty policy_no)', $stats['skipped']],
                ['Errors', count($stats['errors'])],
            ]
        );

        if ($stats['errors'] !== []) {
            $this->warn('Import errors (sample):');

            foreach (array_slice($stats['errors'], 0, 10, true) as $legacyPolicyId => $message) {
                $this->line("  policy_id {$legacyPolicyId}: {$message}");
            }

            return self::FAILURE;
        }

        $this->info($dryRun ? 'Dry run complete.' : 'Legacy doctor policies import complete.');

        return self::SUCCESS;
    }
}

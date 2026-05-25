<?php

namespace App\Console\Commands;

use App\Services\LegacyEmployeeSalaryImportService;
use Illuminate\Console\Command;

class ImportLegacyEmployeeSalaryCommand extends Command
{
    protected $signature = 'legacy:import-employee-salary
                            {--file=storage/app/legacy_tbl_employee_salary.sql : Path to legacy tbl_employee_salary SQL dump}
                            {--sync-only : Sync from staging without loading SQL}
                            {--replace : Remove salary rows whose ids are not in the legacy dump}
                            {--dry-run : Preview counts without writing}
                            {--no-truncate : Append to staging instead of truncating before load}';

    protected $description = 'Import legacy tbl_employee_salary into Manage Salary (salary_records linked by users.legacy_user_id).';

    public function handle(LegacyEmployeeSalaryImportService $importService): int
    {
        $syncOnly = (bool) $this->option('sync-only');
        $replace = (bool) $this->option('replace');
        $dryRun = (bool) $this->option('dry-run');
        $truncate = ! $this->option('no-truncate');
        $file = (string) $this->option('file');

        if (! $syncOnly) {
            if (! is_file($file)) {
                $this->error("SQL file not found: {$file}");

                return self::FAILURE;
            }

            $this->info("Loading SQL from {$file}...");

            try {
                $loaded = $importService->loadSqlFile($file, $truncate);
            } catch (\Throwable $exception) {
                $this->error($exception->getMessage());

                return self::FAILURE;
            }

            $this->info("Executed INSERT statements (~{$loaded} row value groups).");
        }

        $stagingCount = $importService->stagingRowCount();

        if ($stagingCount === 0) {
            $this->warn('No rows in legacy_tbl_employee_salary staging table.');

            return self::SUCCESS;
        }

        $this->info("Salary records — staging rows: {$stagingCount}");

        try {
            $stats = $importService->syncSalaries($replace, $dryRun);
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($dryRun) {
            $this->line('Dry run — no changes written.');
        }

        $this->table(
            ['Metric', 'Count'],
            [
                ['Created', $stats['created']],
                ['Updated', $stats['updated']],
                ['Removed (replace)', $stats['removed']],
                ['Skipped', $stats['skipped']],
            ]
        );

        $this->info('Legacy salary import finished. Rows link employee_id → users.legacy_user_id → salary_records.user_id.');

        return self::SUCCESS;
    }
}

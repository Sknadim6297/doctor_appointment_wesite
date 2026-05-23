<?php

namespace App\Console\Commands;

use App\Services\LegacyCallSheetImportService;
use Illuminate\Console\Command;

class ImportLegacyCallSheetsCommand extends Command
{
    protected $signature = 'legacy:import-call-sheets
                            {--file=storage/app/legacy_tbl_call_sheet.sql : Path to legacy tbl_call_sheet SQL dump}
                            {--sync-only : Sync from staging without loading SQL}
                            {--dry-run : Preview counts without writing}
                            {--no-truncate : Append to staging instead of truncating before load}
                            {--hide-doctors : Hide legacy doctor enrollments from the call sheet list}';

    protected $description = 'Import legacy marketing call sheet rows into enrollments (call sheet module).';

    public function handle(LegacyCallSheetImportService $importService): int
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
            $this->warn('No rows in legacy_tbl_call_sheet staging table.');

            return self::FAILURE;
        }

        $this->info("Staging rows: {$stagingCount}");

        try {
            $stats = $importService->syncFromStaging((bool) $this->option('dry-run'));
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->line('Dry run — no changes written.');
        } else {
            $hidden = $importService->hideLegacyDoctorsFromCallSheet();
            $this->info("Hidden {$hidden} legacy doctor enrollment(s) from call sheet list.");
        }

        if ($this->option('hide-doctors') && $this->option('dry-run')) {
            $this->warn('--hide-doctors has no effect with --dry-run.');
        }

        $this->table(
            ['Metric', 'Count'],
            [
                ['Created', $stats['created']],
                ['Updated', $stats['updated']],
                ['Skipped', $stats['skipped']],
                ['Errors', count($stats['errors'])],
            ]
        );

        if ($stats['errors'] !== []) {
            foreach ($stats['errors'] as $legacyId => $message) {
                $this->warn("Call sheet {$legacyId}: {$message}");
            }

            return self::FAILURE;
        }

        $this->info('Call sheet import finished. Filter by month/year on Marketing → Call Sheet.');

        return self::SUCCESS;
    }
}

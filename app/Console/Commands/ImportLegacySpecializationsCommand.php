<?php

namespace App\Console\Commands;

use App\Services\LegacySpecializationImportService;
use Illuminate\Console\Command;

class ImportLegacySpecializationsCommand extends Command
{
    protected $signature = 'legacy:import-specializations
                            {--file=storage/app/legacy_specialization.sql : Path to legacy specialization SQL dump}
                            {--sync-only : Sync from legacy_specialization staging without loading SQL}
                            {--replace : Remove specializations whose ids are not in the legacy dump}
                            {--dry-run : Preview counts without writing}
                            {--no-truncate : Append to staging instead of truncating before load}';

    protected $description = 'Import legacy specialization rows into specializations with the same ids.';

    public function handle(LegacySpecializationImportService $importService): int
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
            $this->warn('No rows in legacy_specialization staging table.');

            return self::FAILURE;
        }

        $this->info("Staging rows: {$stagingCount}");

        try {
            $stats = $importService->syncToSpecializations(
                (bool) $this->option('replace'),
                (bool) $this->option('dry-run')
            );
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
                ['Created', $stats['created']],
                ['Updated', $stats['updated']],
                ['Removed (replace)', $stats['removed']],
            ]
        );

        $this->info('Legacy specialization import finished. enrollments.specialization_id and insurance_plans.specializations JSON use these ids.');

        return self::SUCCESS;
    }
}

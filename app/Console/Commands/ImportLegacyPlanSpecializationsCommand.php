<?php

namespace App\Console\Commands;

use App\Services\LegacyPlanSpecializationImportService;
use Illuminate\Console\Command;

class ImportLegacyPlanSpecializationsCommand extends Command
{
    protected $signature = 'legacy:import-plan-specializations
                            {--combo-file=storage/app/legacy_tbl_combo_plan_specialization.sql : Combo plan specialization links SQL}
                            {--insurance-file=storage/app/legacy_tbl_insurence_plan_specialization.sql : Insurance plan specialization links SQL}
                            {--insurance-plans-file=storage/app/legacy_tbl_insurence.sql : Legacy tbl_insurence SQL (parent plans)}
                            {--only= : Comma-separated: combo,insurance,insurance-plans}
                            {--sync-only : Sync from staging without loading SQL}
                            {--replace : Replace existing pivot / insurance plan rows not in dump}
                            {--dry-run : Preview counts without writing}
                            {--no-truncate : Append to staging instead of truncating before load}';

    protected $description = 'Import combo/insurance plan to specialization links from legacy tbl_* tables.';

    public function handle(LegacyPlanSpecializationImportService $importService): int
    {
        $only = $this->parseOnlyOption();
        $syncOnly = (bool) $this->option('sync-only');
        $replace = (bool) $this->option('replace');
        $dryRun = (bool) $this->option('dry-run');
        $truncate = !$this->option('no-truncate');
        $failed = false;

        if (in_array('insurance-plans', $only, true)) {
            $failed = $this->importInsurancePlans($importService, $syncOnly, $truncate, $replace, $dryRun) || $failed;
        }

        if (in_array('combo', $only, true)) {
            $failed = $this->importComboLinks($importService, $syncOnly, $truncate, $replace, $dryRun) || $failed;
        }

        if (in_array('insurance', $only, true)) {
            $failed = $this->importInsuranceLinks($importService, $syncOnly, $truncate, $dryRun) || $failed;
        }

        if ($failed) {
            return self::FAILURE;
        }

        $this->info('Plan specialization links imported. Combo coverage filtering and insurance fallback use specialization_id.');

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function parseOnlyOption(): array
    {
        $raw = trim((string) $this->option('only'));

        if ($raw === '') {
            return ['insurance-plans', 'combo', 'insurance'];
        }

        $parts = array_map(static fn (string $part): string => strtolower(trim($part)), explode(',', $raw));

        return array_values(array_intersect(['combo', 'insurance', 'insurance-plans'], $parts))
            ?: ['insurance-plans', 'combo', 'insurance'];
    }

    private function importInsurancePlans(
        LegacyPlanSpecializationImportService $importService,
        bool $syncOnly,
        bool $truncate,
        bool $replace,
        bool $dryRun,
    ): bool {
        $file = (string) $this->option('insurance-plans-file');

        if (!$syncOnly && !$this->loadFile(
            fn (string $path, bool $truncateStaging) => $importService->loadInsurancePlanSqlFile($path, $truncateStaging),
            $file,
            $truncate
        )) {
            return true;
        }

        $count = $importService->stagingRowCount('legacy_tbl_insurence');

        if ($count === 0) {
            $this->warn('No rows in legacy_tbl_insurence staging table.');

            return true;
        }

        $this->info("Insurance plans — staging rows: {$count}");

        try {
            $stats = $importService->syncInsurancePlans($replace, $dryRun);
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return true;
        }

        $this->printStats(['Created', 'Updated', 'Removed (replace)', 'Skipped'], $stats, $dryRun);

        return false;
    }

    private function importComboLinks(
        LegacyPlanSpecializationImportService $importService,
        bool $syncOnly,
        bool $truncate,
        bool $replace,
        bool $dryRun,
    ): bool {
        $file = (string) $this->option('combo-file');

        if (!$syncOnly && !$this->loadFile(
            fn (string $path, bool $truncateStaging) => $importService->loadComboPlanSpecializationSqlFile($path, $truncateStaging),
            $file,
            $truncate
        )) {
            return true;
        }

        $count = $importService->stagingRowCount('legacy_tbl_combo_plan_specialization');

        if ($count === 0) {
            $this->warn('No rows in legacy_tbl_combo_plan_specialization staging table.');

            return true;
        }

        $this->info("Combo plan links — staging rows: {$count}");

        try {
            $stats = $importService->syncComboPlanSpecializations($replace, $dryRun);
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return true;
        }

        $this->printStats(
            ['Links created', 'Links updated', 'Skipped (missing plan/spec)', 'Removed (replace)', 'Combo plans JSON updated'],
            [
                $stats['created'],
                $stats['updated'],
                $stats['skipped'],
                $stats['removed'],
                $stats['combo_plans_updated'],
            ],
            $dryRun
        );

        return false;
    }

    private function importInsuranceLinks(
        LegacyPlanSpecializationImportService $importService,
        bool $syncOnly,
        bool $truncate,
        bool $dryRun,
    ): bool {
        $file = (string) $this->option('insurance-file');

        if (!$syncOnly && !$this->loadFile(
            fn (string $path, bool $truncateStaging) => $importService->loadInsurancePlanSpecializationSqlFile($path, $truncateStaging),
            $file,
            $truncate
        )) {
            return true;
        }

        $count = $importService->stagingRowCount('legacy_tbl_insurence_plan_specialization');

        if ($count === 0) {
            $this->warn('No rows in legacy_tbl_insurence_plan_specialization staging table.');

            return true;
        }

        $this->info("Insurance plan links — staging rows: {$count}");

        try {
            $stats = $importService->syncInsurancePlanSpecializations($dryRun);
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return true;
        }

        $this->printStats(
            ['Links applied', 'Skipped (missing plan/spec)', 'Insurance plans JSON updated'],
            [$stats['links_applied'], $stats['skipped'], $stats['plans_updated']],
            $dryRun
        );

        return false;
    }

    private function loadFile(callable $loader, string $file, bool $truncate): bool
    {
        if (!is_file($file)) {
            $this->error("SQL file not found: {$file}");

            return false;
        }

        $this->info("Loading SQL from {$file}...");

        try {
            $loaded = $loader($file, $truncate);
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return false;
        }

        $this->info("Executed INSERT statements (~{$loaded} row value groups).");

        return true;
    }

    /**
     * @param  array<int, string>  $labels
     * @param  array<int, int>|array<string, int>  $stats
     */
    private function printStats(array $labels, array $stats, bool $dryRun): void
    {
        if ($dryRun) {
            $this->line('Dry run — no changes written.');
        }

        $rows = [];

        if (array_is_list($stats)) {
            foreach ($labels as $index => $label) {
                $rows[] = [$label, $stats[$index] ?? 0];
            }
        } else {
            foreach ($labels as $label) {
                $key = strtolower(str_replace([' ', '(', ')'], ['_', '', ''], $label));
                $rows[] = [$label, $stats[$key] ?? $stats[array_key_first($stats)] ?? 0];
            }
        }

        $this->table(['Metric', 'Count'], $rows);
    }
}

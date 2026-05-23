<?php

namespace App\Console\Commands;

use App\Services\LegacyPlanImportService;
use Illuminate\Console\Command;

class ImportLegacyPlansCommand extends Command
{
    protected $signature = 'legacy:import-plans
                            {--normal-file=storage/app/legacy_normal_plan.sql : Path to legacy normal_plan SQL dump}
                            {--high-file=storage/app/legacy_high_plan.sql : Path to legacy high_plan SQL dump}
                            {--combo-file=storage/app/legacy_combo_plan.sql : Path to legacy combo_plan SQL dump}
                            {--only= : Comma-separated: normal,high,combo (default: all)}
                            {--sync-only : Sync from staging without loading SQL}
                            {--replace : Remove plan rows whose ids are not in the legacy dump}
                            {--dry-run : Preview counts without writing}
                            {--no-truncate : Append to staging instead of truncating before load}';

    protected $description = 'Import legacy normal_plan, high_plan, and combo_plan rows with preserved plan ids for enrollment coverage_id.';

    public function handle(LegacyPlanImportService $importService): int
    {
        $only = $this->parseOnlyOption();
        $syncOnly = (bool) $this->option('sync-only');
        $replace = (bool) $this->option('replace');
        $dryRun = (bool) $this->option('dry-run');
        $truncate = !$this->option('no-truncate');

        $failed = false;

        if (in_array('normal', $only, true)) {
            $failed = $this->importNormal($importService, $syncOnly, $truncate, $replace, $dryRun) || $failed;
        }

        if (in_array('high', $only, true)) {
            $failed = $this->importHigh($importService, $syncOnly, $truncate, $replace, $dryRun) || $failed;
        }

        if (in_array('combo', $only, true)) {
            $failed = $this->importCombo($importService, $syncOnly, $truncate, $replace, $dryRun) || $failed;
        }

        if ($failed) {
            return self::FAILURE;
        }

        $this->info('Legacy plan import finished. enrollments.coverage_id references these ids (plan 1=normal, 2=high risk, 3=combo).');

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function parseOnlyOption(): array
    {
        $raw = trim((string) $this->option('only'));

        if ($raw === '') {
            return ['normal', 'high', 'combo'];
        }

        $parts = array_map(static fn (string $part): string => strtolower(trim($part)), explode(',', $raw));

        return array_values(array_intersect(['normal', 'high', 'combo'], $parts)) ?: ['normal', 'high', 'combo'];
    }

    private function importNormal(LegacyPlanImportService $importService, bool $syncOnly, bool $truncate, bool $replace, bool $dryRun): bool
    {
        $file = (string) $this->option('normal-file');

        if (!$syncOnly && !$this->loadFile(fn (string $path, bool $truncateStaging) => $importService->loadNormalPlanSqlFile($path, $truncateStaging), $file, $truncate)) {
            return true;
        }

        return $this->syncSection('Normal plans', 'legacy_normal_plan', $importService->stagingRowCount('legacy_normal_plan'), fn () => $importService->syncNormalPlans($replace, $dryRun));
    }

    private function importHigh(LegacyPlanImportService $importService, bool $syncOnly, bool $truncate, bool $replace, bool $dryRun): bool
    {
        $file = (string) $this->option('high-file');

        if (!$syncOnly && !$this->loadFile(fn (string $path, bool $truncateStaging) => $importService->loadHighPlanSqlFile($path, $truncateStaging), $file, $truncate)) {
            return true;
        }

        return $this->syncSection('High risk plans', 'legacy_high_plan', $importService->stagingRowCount('legacy_high_plan'), fn () => $importService->syncHighRiskPlans($replace, $dryRun));
    }

    private function importCombo(LegacyPlanImportService $importService, bool $syncOnly, bool $truncate, bool $replace, bool $dryRun): bool
    {
        $file = (string) $this->option('combo-file');

        if (!$syncOnly && !$this->loadFile(fn (string $path, bool $truncateStaging) => $importService->loadComboPlanSqlFile($path, $truncateStaging), $file, $truncate)) {
            return true;
        }

        return $this->syncSection('Combo plans', 'legacy_combo_plan', $importService->stagingRowCount('legacy_combo_plan'), fn () => $importService->syncComboPlans($replace, $dryRun));
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
     * @param  callable(): array{created:int,updated:int,removed:int}  $sync
     */
    private function syncSection(string $label, string $stagingTable, int $stagingCount, callable $sync): bool
    {
        if ($stagingCount === 0) {
            $this->warn("No rows in {$stagingTable} staging table for {$label}.");

            return true;
        }

        $this->info("{$label} — staging rows: {$stagingCount}");

        try {
            $stats = $sync();
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return true;
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

        return false;
    }
}

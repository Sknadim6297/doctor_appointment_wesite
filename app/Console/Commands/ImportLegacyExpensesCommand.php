<?php

namespace App\Console\Commands;

use App\Services\LegacyExpenseImportService;
use Illuminate\Console\Command;

class ImportLegacyExpensesCommand extends Command
{
    protected $signature = 'legacy:import-expenses
                            {--category-file=storage/app/legacy_tbl_expensive_category.sql : Path to legacy tbl_expensive_category SQL dump}
                            {--expense-file=storage/app/legacy_tbl_expensive.sql : Path to legacy tbl_expensive SQL dump}
                            {--only= : Comma-separated: categories,expenses (default: all)}
                            {--sync-only : Sync from staging without loading SQL}
                            {--replace : Remove rows whose ids are not in the legacy dump}
                            {--dry-run : Preview counts without writing}
                            {--no-truncate : Append to staging instead of truncating before load}';

    protected $description = 'Import legacy tbl_expensive_category and tbl_expensive into account management expense tables.';

    public function handle(LegacyExpenseImportService $importService): int
    {
        $only = $this->parseOnlyOption();
        $syncOnly = (bool) $this->option('sync-only');
        $replace = (bool) $this->option('replace');
        $dryRun = (bool) $this->option('dry-run');
        $truncate = ! $this->option('no-truncate');

        $failed = false;

        if (in_array('categories', $only, true)) {
            $failed = $this->importCategories($importService, $syncOnly, $truncate, $replace, $dryRun) || $failed;
        }

        if (in_array('expenses', $only, true)) {
            $failed = $this->importExpenses($importService, $syncOnly, $truncate, $replace, $dryRun) || $failed;
        }

        if ($failed) {
            return self::FAILURE;
        }

        $this->info('Legacy expense import finished. Manage Expense / Manage Expense Category use expense_categories.id and expenses.expense_category_id.');

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function parseOnlyOption(): array
    {
        $raw = trim((string) $this->option('only'));

        if ($raw === '') {
            return ['categories', 'expenses'];
        }

        $parts = array_map(static fn (string $part): string => strtolower(trim($part)), explode(',', $raw));

        return array_values(array_intersect(['categories', 'expenses'], $parts)) ?: ['categories', 'expenses'];
    }

    private function importCategories(LegacyExpenseImportService $importService, bool $syncOnly, bool $truncate, bool $replace, bool $dryRun): bool
    {
        $file = (string) $this->option('category-file');

        if (! $syncOnly && ! $this->loadFile(fn (string $path, bool $truncateStaging) => $importService->loadCategorySqlFile($path, $truncateStaging), $file, $truncate)) {
            return true;
        }

        return $this->syncSection(
            'Expense categories',
            'legacy_tbl_expensive_category',
            $importService->stagingRowCount('legacy_tbl_expensive_category'),
            fn () => $importService->syncCategories($replace, $dryRun),
        );
    }

    private function importExpenses(LegacyExpenseImportService $importService, bool $syncOnly, bool $truncate, bool $replace, bool $dryRun): bool
    {
        $file = (string) $this->option('expense-file');

        if (! $syncOnly && ! $this->loadFile(fn (string $path, bool $truncateStaging) => $importService->loadExpenseSqlFile($path, $truncateStaging), $file, $truncate)) {
            return true;
        }

        return $this->syncSection(
            'Expenses',
            'legacy_tbl_expensive',
            $importService->stagingRowCount('legacy_tbl_expensive'),
            fn () => $importService->syncExpenses($replace, $dryRun),
        );
    }

    private function loadFile(callable $loader, string $file, bool $truncate): bool
    {
        if (! is_file($file)) {
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
     * @param  callable(): array{created:int,updated:int,removed:int,skipped:int}  $sync
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
                ['Skipped', $stats['skipped']],
            ]
        );

        return false;
    }
}

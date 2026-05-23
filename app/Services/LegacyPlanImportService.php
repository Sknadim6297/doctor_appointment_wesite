<?php

namespace App\Services;

use App\Models\ComboPlan;
use App\Models\HighRiskPlan;
use App\Models\NormalPlan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LegacyPlanImportService
{
    public function loadNormalPlanSqlFile(string $path, bool $truncateStaging = true): int
    {
        return $this->loadSqlFile($path, 'normal_plan', 'legacy_normal_plan', $truncateStaging);
    }

    public function loadHighPlanSqlFile(string $path, bool $truncateStaging = true): int
    {
        return $this->loadSqlFile($path, 'high_plan', 'legacy_high_plan', $truncateStaging);
    }

    public function loadComboPlanSqlFile(string $path, bool $truncateStaging = true): int
    {
        return $this->loadSqlFile($path, 'combo_plan', 'legacy_combo_plan', $truncateStaging);
    }

    /**
     * @return array{created:int,updated:int,removed:int}
     */
    public function syncNormalPlans(bool $replaceExisting = false, bool $dryRun = false): array
    {
        return $this->syncTable(
            stagingTable: 'legacy_normal_plan',
            targetTable: 'normal_plans',
            idColumn: 'plan_id',
            replaceExisting: $replaceExisting,
            dryRun: $dryRun,
            mapper: fn (object $row): array => $this->mapPricingRow($row),
        );
    }

    /**
     * @return array{created:int,updated:int,removed:int}
     */
    public function syncHighRiskPlans(bool $replaceExisting = false, bool $dryRun = false): array
    {
        return $this->syncTable(
            stagingTable: 'legacy_high_plan',
            targetTable: 'high_risk_plans',
            idColumn: 'plan_id',
            replaceExisting: $replaceExisting,
            dryRun: $dryRun,
            mapper: fn (object $row): array => $this->mapPricingRow($row),
        );
    }

    /**
     * @return array{created:int,updated:int,removed:int}
     */
    public function syncComboPlans(bool $replaceExisting = false, bool $dryRun = false): array
    {
        return $this->syncTable(
            stagingTable: 'legacy_combo_plan',
            targetTable: 'combo_plans',
            idColumn: 'plan_id',
            replaceExisting: $replaceExisting,
            dryRun: $dryRun,
            mapper: fn (object $row): array => array_merge(
                $this->mapPricingRow($row),
                ['specializations' => json_encode($this->parseComboSpecializations($row->speciliaziation ?? ''))],
            ),
        );
    }

    public function stagingRowCount(string $stagingTable): int
    {
        if (!Schema::hasTable($stagingTable)) {
            return 0;
        }

        return (int) DB::table($stagingTable)->count();
    }

    private function loadSqlFile(string $path, string $legacyTable, string $stagingTable, bool $truncateStaging): int
    {
        if (!is_file($path)) {
            throw new \InvalidArgumentException("SQL file not found: {$path}");
        }

        if (!Schema::hasTable($stagingTable)) {
            throw new \RuntimeException("Run migrations first ({$stagingTable} table missing).");
        }

        $sql = file_get_contents($path);

        if ($sql === false || trim($sql) === '') {
            throw new \InvalidArgumentException('SQL file is empty or unreadable.');
        }

        $sql = str_replace("`{$legacyTable}`", "`{$stagingTable}`", $sql);
        $sql = preg_replace('/CREATE\s+TABLE\s+`?(?:' . preg_quote($stagingTable, '/') . '|' . preg_quote($legacyTable, '/') . ')`?.*?;\s*/is', '', $sql) ?? $sql;
        $sql = preg_replace('/ALTER\s+TABLE\s+`?(?:' . preg_quote($stagingTable, '/') . '|' . preg_quote($legacyTable, '/') . ')`?.*?;\s*/is', '', $sql) ?? $sql;

        if ($truncateStaging) {
            DB::table($stagingTable)->truncate();
        }

        $inserted = 0;

        foreach ($this->splitSqlStatements($sql) as $statement) {
            $statement = $this->stripSqlComments($statement);

            if ($statement === '' || !preg_match('/INSERT\s+INTO\s+`?' . preg_quote($stagingTable, '/') . '`?/i', $statement)) {
                continue;
            }

            try {
                DB::unprepared($statement);
                $inserted += $this->countInsertRows($statement);
            } catch (\Throwable $exception) {
                throw new \RuntimeException(
                    "Failed executing {$stagingTable} INSERT: " . $exception->getMessage(),
                    (int) $exception->getCode(),
                    $exception
                );
            }
        }

        return $inserted;
    }

    /**
     * @param  callable(object): array<string, mixed>  $mapper
     * @return array{created:int,updated:int,removed:int}
     */
    private function syncTable(
        string $stagingTable,
        string $targetTable,
        string $idColumn,
        bool $replaceExisting,
        bool $dryRun,
        callable $mapper,
    ): array {
        if (!Schema::hasTable($stagingTable)) {
            throw new \RuntimeException("{$stagingTable} table missing.");
        }

        $stats = ['created' => 0, 'updated' => 0, 'removed' => 0];
        $stagingIds = DB::table($stagingTable)->orderBy($idColumn)->pluck($idColumn)->map(fn ($id) => (int) $id)->all();

        if ($stagingIds === []) {
            return $stats;
        }

        if ($dryRun) {
            foreach ($stagingIds as $id) {
                if (DB::table($targetTable)->where('id', $id)->exists()) {
                    $stats['updated']++;
                } else {
                    $stats['created']++;
                }
            }

            if ($replaceExisting) {
                $stats['removed'] = DB::table($targetTable)->whereNotIn('id', $stagingIds)->count();
            }

            return $stats;
        }

        DB::transaction(function () use ($replaceExisting, $stagingIds, $stagingTable, $targetTable, $idColumn, $mapper, &$stats) {
            if ($replaceExisting) {
                $stats['removed'] = DB::table($targetTable)->whereNotIn('id', $stagingIds)->delete();
            }

            $now = now();

            DB::table($stagingTable)->orderBy($idColumn)->chunk(100, function ($rows) use ($targetTable, $idColumn, $mapper, $now, &$stats) {
                foreach ($rows as $row) {
                    $id = (int) $row->{$idColumn};

                    if ($id <= 0) {
                        continue;
                    }

                    $payload = array_merge($mapper($row), [
                        'updated_at' => $now,
                    ]);

                    if (DB::table($targetTable)->where('id', $id)->exists()) {
                        DB::table($targetTable)->where('id', $id)->update($payload);
                        $stats['updated']++;
                    } else {
                        DB::table($targetTable)->insert(array_merge($payload, [
                            'id' => $id,
                            'created_at' => $now,
                        ]));
                        $stats['created']++;
                    }
                }
            });
        });

        $this->resetAutoIncrement($targetTable);

        return $stats;
    }

    /**
     * @return array<string, mixed>
     */
    private function mapPricingRow(object $row): array
    {
        return [
            'coverage_lakh' => (float) $row->coverage,
            'yearly_amount' => (float) $row->yearly_plan,
            'monthly_amount' => $this->nullableFloat($row->monthly_plan ?? null),
            'two_year_amount' => $this->nullableFloat($row->two_year ?? null),
            'three_year_amount' => $this->nullableFloat($row->three_year ?? null),
            'four_year_amount' => $this->nullableFloat($row->four_year ?? null),
            'five_year_amount' => $this->nullableFloat($row->five_year ?? null),
        ];
    }

    /**
     * @return array<int, string>
     */
    public function parseComboSpecializations(mixed $value): array
    {
        $raw = trim((string) $value);

        if ($raw === '') {
            return [];
        }

        $parts = preg_split('/\s*,\s*/', $raw) ?: [];

        return array_values(array_filter(array_map(static fn (string $part): string => trim($part), $parts), static fn (string $part): bool => $part !== ''));
    }

    private function nullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }

    private function resetAutoIncrement(string $table): void
    {
        $maxId = (int) DB::table($table)->max('id');
        $next = max($maxId + 1, 1);
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('DELETE FROM sqlite_sequence WHERE name = ?', [$table]);
            DB::statement('INSERT INTO sqlite_sequence (name, seq) VALUES (?, ?)', [$table, $maxId]);
        } elseif ($driver === 'mysql') {
            DB::statement("ALTER TABLE {$table} AUTO_INCREMENT = {$next}");
        }
    }

    /**
     * @return array<int, string>
     */
    private function splitSqlStatements(string $sql): array
    {
        $sql = str_replace(["\r\n", "\r"], "\n", $sql);
        $parts = preg_split('/;\s*\n/', $sql) ?: [];

        return array_values(array_filter(array_map(static function (string $part): string {
            return rtrim(trim($part), ';');
        }, $parts), static fn (string $part): bool => $part !== ''));
    }

    private function countInsertRows(string $statement): int
    {
        return substr_count($statement, '),(') + 1;
    }

    private function stripSqlComments(string $sql): string
    {
        $sql = preg_replace('/^--.*$/m', '', $sql) ?? $sql;

        return trim($sql);
    }
}

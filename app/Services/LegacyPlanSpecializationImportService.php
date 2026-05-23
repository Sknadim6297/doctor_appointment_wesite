<?php

namespace App\Services;

use App\Models\ComboPlan;
use App\Models\InsurancePlan;
use App\Models\Specialization;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LegacyPlanSpecializationImportService
{
    public function loadComboPlanSpecializationSqlFile(string $path, bool $truncateStaging = true): int
    {
        return $this->loadSqlFile(
            $path,
            'tbl_combo_plan_specialization',
            'legacy_tbl_combo_plan_specialization',
            $truncateStaging
        );
    }

    public function loadInsurancePlanSpecializationSqlFile(string $path, bool $truncateStaging = true): int
    {
        return $this->loadSqlFile(
            $path,
            'tbl_insurence_plan_specialization',
            'legacy_tbl_insurence_plan_specialization',
            $truncateStaging
        );
    }

    public function loadInsurancePlanSqlFile(string $path, bool $truncateStaging = true): int
    {
        return $this->loadSqlFile(
            $path,
            'tbl_insurence',
            'legacy_tbl_insurence',
            $truncateStaging
        );
    }

    /**
     * @return array{created:int,updated:int,skipped:int,removed:int,combo_plans_updated:int}
     */
    public function syncComboPlanSpecializations(bool $replaceExisting = false, bool $dryRun = false): array
    {
        $this->assertStagingTable('legacy_tbl_combo_plan_specialization');

        $stats = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'removed' => 0,
            'combo_plans_updated' => 0,
        ];

        $validComboIds = ComboPlan::query()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $validComboLookup = array_fill_keys($validComboIds, true);
        $validSpecIds = Specialization::query()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $validSpecLookup = array_fill_keys($validSpecIds, true);

        $rows = DB::table('legacy_tbl_combo_plan_specialization')->orderBy('id')->get();
        $groupedSpecIds = [];

        if ($replaceExisting && !$dryRun) {
            $stats['removed'] = (int) DB::table('combo_plan_specialization')->count();
            DB::table('combo_plan_specialization')->truncate();
        }

        foreach ($rows as $row) {
            $comboPlanId = (int) $row->combo_plan_id;
            $specializationId = (int) $row->specialization_id;
            $legacyLinkId = (int) $row->id;

            if (!isset($validComboLookup[$comboPlanId]) || !isset($validSpecLookup[$specializationId])) {
                $stats['skipped']++;

                continue;
            }

            $groupedSpecIds[$comboPlanId] ??= [];
            $groupedSpecIds[$comboPlanId][$specializationId] = $specializationId;

            if ($dryRun) {
                $exists = DB::table('combo_plan_specialization')
                    ->where('combo_plan_id', $comboPlanId)
                    ->where('specialization_id', $specializationId)
                    ->exists();

                $exists ? $stats['updated']++ : $stats['created']++;

                continue;
            }

            $existing = DB::table('combo_plan_specialization')
                ->where('combo_plan_id', $comboPlanId)
                ->where('specialization_id', $specializationId)
                ->first();

            if ($existing) {
                DB::table('combo_plan_specialization')->where('id', $existing->id)->update([
                    'legacy_link_id' => $legacyLinkId,
                    'updated_at' => now(),
                ]);
                $stats['updated']++;
            } else {
                DB::table('combo_plan_specialization')->insert([
                    'combo_plan_id' => $comboPlanId,
                    'specialization_id' => $specializationId,
                    'legacy_link_id' => $legacyLinkId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $stats['created']++;
            }
        }

        if (!$dryRun) {
            foreach ($groupedSpecIds as $comboPlanId => $specIds) {
                $ids = array_values(array_map('intval', $specIds));
                sort($ids);

                ComboPlan::query()->whereKey($comboPlanId)->update([
                    'specializations' => $ids,
                ]);
                $stats['combo_plans_updated']++;
            }
        } elseif ($groupedSpecIds !== []) {
            $stats['combo_plans_updated'] = count($groupedSpecIds);
        }

        return $stats;
    }

    /**
     * @return array{created:int,updated:int,skipped:int,removed:int}
     */
    public function syncInsurancePlans(bool $replaceExisting = false, bool $dryRun = false): array
    {
        $this->assertStagingTable('legacy_tbl_insurence');

        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'removed' => 0];
        $stagingIds = DB::table('legacy_tbl_insurence')->orderBy('insurence_id')->pluck('insurence_id')->map(fn ($id) => (int) $id)->all();

        if ($stagingIds === []) {
            return $stats;
        }

        if ($dryRun) {
            foreach ($stagingIds as $id) {
                DB::table('insurance_plans')->where('id', $id)->exists()
                    ? $stats['updated']++
                    : $stats['created']++;
            }

            if ($replaceExisting) {
                $stats['removed'] = DB::table('insurance_plans')->whereNotIn('id', $stagingIds)->count();
            }

            return $stats;
        }

        DB::transaction(function () use ($replaceExisting, $stagingIds, &$stats) {
            if ($replaceExisting) {
                $stats['removed'] = DB::table('insurance_plans')->whereNotIn('id', $stagingIds)->delete();
            }

            $now = now();

            DB::table('legacy_tbl_insurence')->orderBy('insurence_id')->chunk(100, function ($rows) use ($now, &$stats) {
                foreach ($rows as $row) {
                    $id = (int) $row->insurence_id;

                    if ($id <= 0) {
                        $stats['skipped']++;

                        continue;
                    }

                    $payload = [
                        'amount_per_lakh' => (float) $row->amount,
                        'service_tax_percent' => (float) $row->tax,
                        'yearly_amount' => (float) $row->yearly_plan,
                        'two_year_amount' => (float) $row->two_year,
                        'three_year_amount' => (float) $row->three_year,
                        'four_year_amount' => (float) $row->four_year,
                        'five_year_amount' => (float) $row->five_year,
                        'updated_at' => $now,
                    ];

                    if (DB::table('insurance_plans')->where('id', $id)->exists()) {
                        DB::table('insurance_plans')->where('id', $id)->update($payload);
                        $stats['updated']++;
                    } else {
                        DB::table('insurance_plans')->insert(array_merge($payload, [
                            'id' => $id,
                            'specializations' => json_encode([]),
                            'created_at' => $now,
                        ]));
                        $stats['created']++;
                    }
                }
            });
        });

        $this->resetAutoIncrement('insurance_plans');

        return $stats;
    }

    /**
     * @return array{plans_updated:int,links_applied:int,skipped:int}
     */
    public function syncInsurancePlanSpecializations(bool $dryRun = false): array
    {
        $this->assertStagingTable('legacy_tbl_insurence_plan_specialization');

        $stats = ['plans_updated' => 0, 'links_applied' => 0, 'skipped' => 0];
        $validPlanIds = DB::table('insurance_plans')->pluck('id')->map(fn ($id) => (int) $id)->all();
        $validPlanLookup = array_fill_keys($validPlanIds, true);
        $validSpecIds = Specialization::query()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $validSpecLookup = array_fill_keys($validSpecIds, true);

        $grouped = [];

        foreach (DB::table('legacy_tbl_insurence_plan_specialization')->orderBy('id')->get() as $row) {
            $planId = (int) $row->insurence_plan_id;
            $specializationId = (int) $row->specialization_id;

            if (!isset($validPlanLookup[$planId]) || !isset($validSpecLookup[$specializationId])) {
                $stats['skipped']++;

                continue;
            }

            $grouped[$planId] ??= [];
            $grouped[$planId][$specializationId] = $specializationId;
            $stats['links_applied']++;
        }

        if ($dryRun) {
            $stats['plans_updated'] = count($grouped);

            return $stats;
        }

        $now = now();

        foreach ($grouped as $planId => $specIds) {
            $ids = array_values(array_map('intval', $specIds));
            sort($ids);

            DB::table('insurance_plans')->where('id', $planId)->update([
                'specializations' => json_encode($ids),
                'updated_at' => $now,
            ]);
            $stats['plans_updated']++;
        }

        return $stats;
    }

    public function stagingRowCount(string $table): int
    {
        return Schema::hasTable($table) ? (int) DB::table($table)->count() : 0;
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

    private function assertStagingTable(string $table): void
    {
        if (!Schema::hasTable($table)) {
            throw new \RuntimeException("{$table} table missing.");
        }
    }

    private function resetAutoIncrement(string $table): void
    {
        $maxId = (int) DB::table($table)->max('id');
        $next = max($maxId + 1, 1);
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE {$table} AUTO_INCREMENT = {$next}");
        }
    }

    /**
     * @return array<int, string>
     */
    private function splitSqlStatements(string $sql): array
    {
        $sql = str_replace(["\r\n", "\r"], "\n", $sql);
        $parts = preg_split('/;\s*\n/', $sql) ?? [];

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

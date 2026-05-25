<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LegacyEmployeeSalaryImportService
{
    /** @var array<int, int> */
    private array $userIdByLegacy = [];

    /** @var array<int, int|null> */
    private array $userIdByLegacyCreator = [];

    public function loadSqlFile(string $path, bool $truncateStaging = true): int
    {
        if (! is_file($path)) {
            throw new \InvalidArgumentException("SQL file not found: {$path}");
        }

        if (! Schema::hasTable('legacy_tbl_employee_salary')) {
            throw new \RuntimeException('Run migrations first (legacy_tbl_employee_salary table missing).');
        }

        $sql = file_get_contents($path);

        if ($sql === false || trim($sql) === '') {
            throw new \InvalidArgumentException('SQL file is empty or unreadable.');
        }

        $sql = str_replace('`tbl_employee_salary`', '`legacy_tbl_employee_salary`', $sql);
        $sql = preg_replace('/CREATE\s+TABLE\s+`?(?:legacy_tbl_employee_salary|tbl_employee_salary)`?.*?;\s*/is', '', $sql) ?? $sql;
        $sql = preg_replace('/ALTER\s+TABLE\s+`?(?:legacy_tbl_employee_salary|tbl_employee_salary)`?.*?;\s*/is', '', $sql) ?? $sql;

        if ($truncateStaging) {
            DB::table('legacy_tbl_employee_salary')->truncate();
        }

        $inserted = 0;

        foreach ($this->splitSqlStatements($sql) as $statement) {
            $statement = $this->stripSqlComments($statement);

            if ($statement === '' || ! preg_match('/INSERT\s+INTO\s+`?legacy_tbl_employee_salary`?/i', $statement)) {
                continue;
            }

            try {
                DB::unprepared($statement);
                $inserted += $this->countInsertRows($statement);
            } catch (\Throwable) {
                $inserted += $this->insertParsedSalaryRows($statement);
            }
        }

        return $inserted;
    }

    /**
     * @return array{created:int,updated:int,removed:int,skipped:int}
     */
    public function syncSalaries(bool $replaceExisting = false, bool $dryRun = false): array
    {
        if (! Schema::hasTable('legacy_tbl_employee_salary')) {
            throw new \RuntimeException('legacy_tbl_employee_salary table missing.');
        }

        $this->warmUserMaps();

        $stats = ['created' => 0, 'updated' => 0, 'removed' => 0, 'skipped' => 0];
        $stagingIds = $this->dedupedStagingSalaryIds();

        if ($stagingIds === []) {
            return $stats;
        }

        if ($dryRun) {
            foreach ($stagingIds as $id) {
                if (DB::table('salary_records')->where('id', $id)->exists()) {
                    $stats['updated']++;
                } else {
                    $stats['created']++;
                }
            }

            if ($replaceExisting) {
                $stats['removed'] = DB::table('salary_records')->whereNotIn('id', $stagingIds)->count();
            }

            $this->countSkippedRows($stats);

            return $stats;
        }

        DB::transaction(function () use ($replaceExisting, $stagingIds, &$stats) {
            if ($replaceExisting) {
                $stats['removed'] = DB::table('salary_records')->whereNotIn('id', $stagingIds)->delete();
            }

            $now = now();

            foreach ($this->dedupedStagingRows() as $row) {
                if ($this->shouldSkipRow($row)) {
                    $stats['skipped']++;

                    continue;
                }

                $id = (int) $row->salary_id;
                $payload = array_merge($this->mapRow($row), [
                    'updated_at' => $now,
                ]);

                if (DB::table('salary_records')->where('id', $id)->exists()) {
                    DB::table('salary_records')->where('id', $id)->update($payload);
                    $stats['updated']++;
                } else {
                    DB::table('salary_records')->insert(array_merge($payload, [
                        'id' => $id,
                        'created_at' => $now,
                    ]));
                    $stats['created']++;
                }
            }
        });

        $this->resetAutoIncrement('salary_records');

        return $stats;
    }

    public function stagingRowCount(): int
    {
        if (! Schema::hasTable('legacy_tbl_employee_salary')) {
            return 0;
        }

        return (int) DB::table('legacy_tbl_employee_salary')->count();
    }

    private function warmUserMaps(): void
    {
        if ($this->userIdByLegacy !== []) {
            return;
        }

        $this->userIdByLegacy = User::query()
            ->whereNotNull('legacy_user_id')
            ->pluck('id', 'legacy_user_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $this->userIdByLegacyCreator = $this->userIdByLegacy;
    }

    /**
     * @return array<int, int>
     */
    private function dedupedStagingSalaryIds(): array
    {
        return DB::table('legacy_tbl_employee_salary as s')
            ->joinSub(
                DB::table('legacy_tbl_employee_salary')
                    ->selectRaw('employee_id, month, year, MAX(salary_id) as max_salary_id')
                    ->groupBy('employee_id', 'month', 'year'),
                'latest',
                fn ($join) => $join->on('s.salary_id', '=', 'latest.max_salary_id'),
            )
            ->orderBy('s.salary_id')
            ->pluck('s.salary_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @return iterable<object>
     */
    private function dedupedStagingRows(): iterable
    {
        $ids = $this->dedupedStagingSalaryIds();

        foreach (array_chunk($ids, 100) as $chunk) {
            $rows = DB::table('legacy_tbl_employee_salary')
                ->whereIn('salary_id', $chunk)
                ->orderBy('salary_id')
                ->get();

            foreach ($rows as $row) {
                yield $row;
            }
        }
    }

    /**
     * @param  array{created:int,updated:int,removed:int,skipped:int}  $stats
     */
    private function countSkippedRows(array &$stats): void
    {
        foreach ($this->dedupedStagingRows() as $row) {
            if ($this->shouldSkipRow($row)) {
                $stats['skipped']++;
            }
        }

        $stats['skipped'] += max(0, $this->stagingRowCount() - count($this->dedupedStagingSalaryIds()));
    }

    private function shouldSkipRow(object $row): bool
    {
        $legacyEmployeeId = (int) ($row->employee_id ?? 0);

        if ($legacyEmployeeId <= 0 || ! isset($this->userIdByLegacy[$legacyEmployeeId])) {
            return true;
        }

        return $this->normalizeMonth($row->month ?? null) === null
            || $this->normalizeYear($row->year ?? null) === null;
    }

    /**
     * @return array<string, mixed>
     */
    private function mapRow(object $row): array
    {
        $legacyEmployeeId = (int) $row->employee_id;
        $createdByLegacy = (int) ($row->created_by ?? 0);
        $editedByLegacy = (int) ($row->edited_by ?? 0);

        return [
            'user_id' => $this->userIdByLegacy[$legacyEmployeeId],
            'salary_year' => $this->normalizeYear($row->year ?? null),
            'salary_month' => $this->normalizeMonth($row->month ?? null),
            'monthly_salary' => $this->parseDecimal($row->monthly_salary ?? 0),
            'total_login_day' => $this->nullableSmallInt($row->total_login_day ?? null),
            'total_absense' => max(0, (int) ($row->absense ?? 0)),
            'absense_reason' => $this->nullableString($row->absense_reason ?? null),
            'incentive' => $this->parseDecimal($row->intensive ?? 0),
            'incentive_for' => $this->nullableString($row->intensive_for ?? null),
            'advance' => $this->parseDecimal($row->advance ?? 0),
            'additional_deduct' => $this->parseDecimal($row->additional_deduction ?? 0),
            'additional_deduct_reason' => $this->nullableString($row->additional_deduction_reason ?? null),
            'office_duty' => $this->parseDecimal($row->office_duty ?? 0),
            'bonus' => $this->parseDecimal($row->bonus ?? 0),
            'pf' => $this->parseDecimal($row->pf ?? 0),
            'esi' => $this->parseDecimal($row->esi ?? 0),
            'ptax' => $this->parseDecimal($row->ptax ?? 0),
            'cheque_no' => $this->nullableString($row->checque_no ?? null),
            'bank_name' => $this->nullableString($row->bank_name ?? null),
            'net_salary' => $this->parseDecimal($row->total_salary ?? 0),
            'created_by' => $this->resolveCreatorId($createdByLegacy),
            'updated_by' => $this->resolveCreatorId($editedByLegacy),
        ];
    }

    private function resolveCreatorId(int $legacyUserId): ?int
    {
        if ($legacyUserId <= 0) {
            return null;
        }

        return $this->userIdByLegacyCreator[$legacyUserId] ?? null;
    }

    private function normalizeMonth(mixed $value): ?string
    {
        $month = trim((string) $value);
        if ($month === '') {
            return null;
        }

        $month = ucfirst(strtolower($month));
        $valid = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December',
        ];

        return in_array($month, $valid, true) ? $month : null;
    }

    private function normalizeYear(mixed $value): ?int
    {
        $year = (int) trim((string) $value);

        return $year >= 2011 && $year <= 2100 ? $year : null;
    }

    private function parseDecimal(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_numeric($value)) {
            return round((float) $value, 2);
        }

        $clean = preg_replace('/[^\d.-]/', '', (string) $value);

        return is_numeric($clean) ? round((float) $clean, 2) : 0.0;
    }

    private function nullableSmallInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $int = (int) $value;

        return $int >= 0 && $int <= 31 ? $int : null;
    }

    private function nullableString(mixed $value): ?string
    {
        $raw = trim((string) $value);

        return $raw === '' || strcasecmp($raw, 'NILL') === 0 || strcasecmp($raw, 'NIL') === 0 || strcasecmp($raw, 'N.A') === 0
            ? null
            : $raw;
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

    private function insertParsedSalaryRows(string $insertStatement): int
    {
        $inserted = 0;

        foreach ($this->parseInsertValueTuples($insertStatement) as $tuple) {
            if (count($tuple) < 28) {
                continue;
            }

            $salaryId = (int) ($tuple[0] ?? 0);
            if ($salaryId <= 0) {
                continue;
            }

            DB::table('legacy_tbl_employee_salary')->updateOrInsert(
                ['salary_id' => $salaryId],
                [
                    'employee_id' => (int) ($tuple[1] ?? 0),
                    'month' => (string) ($tuple[2] ?? ''),
                    'year' => (string) ($tuple[3] ?? ''),
                    'monthly_salary' => (string) ($tuple[4] ?? '0'),
                    'total_login_day' => (int) ($tuple[5] ?? 0),
                    'earned_salary' => (string) ($tuple[6] ?? '0'),
                    'deducted_salary' => (string) ($tuple[7] ?? '0'),
                    'additional_deduction' => (string) ($tuple[8] ?? '0'),
                    'additional_deduction_reason' => (string) ($tuple[9] ?? ''),
                    'intensive' => (string) ($tuple[10] ?? '0'),
                    'intensive_for' => (string) ($tuple[11] ?? ''),
                    'advance' => (string) ($tuple[12] ?? '0'),
                    'advance_deduction' => (string) ($tuple[13] ?? '0'),
                    'office_duty' => (string) ($tuple[14] ?? '0'),
                    'bonus' => (string) ($tuple[15] ?? '0'),
                    'pf' => (string) ($tuple[16] ?? '0'),
                    'ptax' => (string) ($tuple[17] ?? '0'),
                    'esi' => (string) ($tuple[18] ?? '0'),
                    'total_salary' => (string) ($tuple[19] ?? '0'),
                    'absense' => (int) ($tuple[20] ?? 0),
                    'absense_reason' => (string) ($tuple[21] ?? ''),
                    'checque_no' => (string) ($tuple[22] ?? ''),
                    'bank_name' => (string) ($tuple[23] ?? ''),
                    'created_date' => $this->nullableDateString($tuple[24] ?? null),
                    'created_by' => (int) ($tuple[25] ?? 0),
                    'edited_date' => $this->nullableDateString($tuple[26] ?? null),
                    'edited_by' => (int) ($tuple[27] ?? 0),
                ],
            );

            $inserted++;
        }

        return $inserted;
    }

    private function nullableDateString(?string $value): ?string
    {
        $raw = trim((string) $value);

        if ($raw === '' || $raw === '0000-00-00' || strcasecmp($raw, 'NULL') === 0) {
            return null;
        }

        return $raw;
    }

    /**
     * @return array<int, array<int, string|null>>
     */
    private function parseInsertValueTuples(string $sql): array
    {
        if (! preg_match('/VALUES\s*(.*)/is', $sql, $matches)) {
            return [];
        }

        $body = rtrim(trim($matches[1]), ";\n\r \t");
        $tuples = [];
        $len = strlen($body);
        $index = 0;

        while ($index < $len) {
            while ($index < $len && in_array($body[$index], [',', ' ', "\n", "\r", "\t"], true)) {
                $index++;
            }

            if ($index >= $len || $body[$index] !== '(') {
                break;
            }

            $index++;
            $fields = [];
            $current = '';
            $inString = false;
            $closed = false;

            while ($index < $len) {
                $char = $body[$index];

                if ($inString) {
                    if ($char === "'") {
                        if ($index + 1 < $len && $body[$index + 1] === "'") {
                            $current .= "'";
                            $index += 2;

                            continue;
                        }

                        $inString = false;
                        $index++;

                        continue;
                    }

                    $current .= $char;
                    $index++;

                    continue;
                }

                if ($char === "'") {
                    $inString = true;
                    $index++;

                    continue;
                }

                if ($char === ')') {
                    $fields[] = $this->normalizeSqlValue($current);
                    $index++;
                    $closed = true;

                    break;
                }

                if ($char === ',') {
                    $fields[] = $this->normalizeSqlValue($current);
                    $current = '';
                    $index++;

                    continue;
                }

                $current .= $char;
                $index++;
            }

            if ($closed && count($fields) >= 28) {
                $tuples[] = array_slice($fields, 0, 28);
            }
        }

        return $tuples;
    }

    private function normalizeSqlValue(string $raw): ?string
    {
        $raw = trim($raw);

        if ($raw === '' || strcasecmp($raw, 'NULL') === 0) {
            return null;
        }

        return $raw;
    }
}

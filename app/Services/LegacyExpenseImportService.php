<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LegacyExpenseImportService
{
    public function loadCategorySqlFile(string $path, bool $truncateStaging = true): int
    {
        return $this->loadSqlFile($path, 'tbl_expensive_category', 'legacy_tbl_expensive_category', $truncateStaging);
    }

    public function loadExpenseSqlFile(string $path, bool $truncateStaging = true): int
    {
        if (! is_file($path)) {
            throw new \InvalidArgumentException("SQL file not found: {$path}");
        }

        if (! Schema::hasTable('legacy_tbl_expensive')) {
            throw new \RuntimeException('Run migrations first (legacy_tbl_expensive table missing).');
        }

        $sql = file_get_contents($path);

        if ($sql === false || trim($sql) === '') {
            throw new \InvalidArgumentException('SQL file is empty or unreadable.');
        }

        $sql = str_replace('`tbl_expensive`', '`legacy_tbl_expensive`', $sql);
        $sql = preg_replace('/CREATE\s+TABLE\s+`?(?:legacy_tbl_expensive|tbl_expensive)`?.*?;\s*/is', '', $sql) ?? $sql;
        $sql = preg_replace('/ALTER\s+TABLE\s+`?(?:legacy_tbl_expensive|tbl_expensive)`?.*?;\s*/is', '', $sql) ?? $sql;

        if ($truncateStaging) {
            DB::table('legacy_tbl_expensive')->truncate();
        }

        $inserted = 0;

        foreach ($this->splitSqlStatements($sql) as $statement) {
            $statement = $this->stripSqlComments($statement);

            if ($statement === '' || ! preg_match('/INSERT\s+INTO\s+`?legacy_tbl_expensive`?/i', $statement)) {
                continue;
            }

            try {
                DB::unprepared($statement);
                $inserted += $this->countInsertRows($statement);
            } catch (\Throwable) {
                $inserted += $this->insertParsedExpenseRows($statement);
            }
        }

        return $inserted;
    }

    /**
     * @return array{created:int,updated:int,removed:int,skipped:int}
     */
    public function syncCategories(bool $replaceExisting = false, bool $dryRun = false): array
    {
        return $this->syncTable(
            stagingTable: 'legacy_tbl_expensive_category',
            targetTable: 'expense_categories',
            idColumn: 'expensive_cat_id',
            replaceExisting: $replaceExisting,
            dryRun: $dryRun,
            mapper: fn (object $row): array => [
                'name' => trim((string) ($row->expensive_cat_name ?? '')),
            ],
            skipWhen: fn (object $row): bool => trim((string) ($row->expensive_cat_name ?? '')) === '',
        );
    }

    /**
     * @return array{created:int,updated:int,removed:int,skipped:int}
     */
    public function syncExpenses(bool $replaceExisting = false, bool $dryRun = false): array
    {
        $categoryIds = DB::table('expense_categories')->pluck('id')->map(fn ($id) => (int) $id)->all();
        $categoryIdSet = array_fill_keys($categoryIds, true);

        return $this->syncTable(
            stagingTable: 'legacy_tbl_expensive',
            targetTable: 'expenses',
            idColumn: 'expense_id',
            replaceExisting: $replaceExisting,
            dryRun: $dryRun,
            mapper: fn (object $row): array => [
                'expense_category_id' => (int) $row->expense_cat_id,
                'expense_date' => (string) $row->expense_date,
                'amount' => $this->parseAmount($row->expense_amount ?? null),
                'payment_mode' => $this->normalizePaymentMode($row->payment_mode ?? null),
                'cheque_no' => $this->nullableString($row->cheque_no ?? null),
                'bank_name' => $this->nullableString($row->bank_name ?? null),
                'customer_name' => $this->nullableString($row->customer_name ?? null),
                'voucher_file' => $this->nullableString($row->voucher ?? null),
                'remarks' => $this->nullableString($row->note ?? null),
            ],
            skipWhen: function (object $row) use ($categoryIdSet): bool {
                $categoryId = (int) ($row->expense_cat_id ?? 0);

                if ($categoryId <= 0 || ! isset($categoryIdSet[$categoryId])) {
                    return true;
                }

                $date = trim((string) ($row->expense_date ?? ''));

                return $date === '' || $this->parseAmount($row->expense_amount ?? null) === null;
            },
        );
    }

    public function stagingRowCount(string $stagingTable): int
    {
        if (! Schema::hasTable($stagingTable)) {
            return 0;
        }

        return (int) DB::table($stagingTable)->count();
    }

    private function loadSqlFile(string $path, string $legacyTable, string $stagingTable, bool $truncateStaging): int
    {
        if (! is_file($path)) {
            throw new \InvalidArgumentException("SQL file not found: {$path}");
        }

        if (! Schema::hasTable($stagingTable)) {
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

            if ($statement === '' || ! preg_match('/INSERT\s+INTO\s+`?' . preg_quote($stagingTable, '/') . '`?/i', $statement)) {
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
     * @param  callable(object): bool  $skipWhen
     * @return array{created:int,updated:int,removed:int,skipped:int}
     */
    private function syncTable(
        string $stagingTable,
        string $targetTable,
        string $idColumn,
        bool $replaceExisting,
        bool $dryRun,
        callable $mapper,
        callable $skipWhen,
    ): array {
        if (! Schema::hasTable($stagingTable)) {
            throw new \RuntimeException("{$stagingTable} table missing.");
        }

        $stats = ['created' => 0, 'updated' => 0, 'removed' => 0, 'skipped' => 0];
        $stagingIds = [];

        DB::table($stagingTable)->orderBy($idColumn)->chunk(200, function ($rows) use ($idColumn, $skipWhen, &$stagingIds) {
            foreach ($rows as $row) {
                $id = (int) $row->{$idColumn};
                if ($id <= 0 || $skipWhen($row)) {
                    continue;
                }
                $stagingIds[] = $id;
            }
        });

        $stagingIds = array_values(array_unique($stagingIds));

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

            DB::table($stagingTable)->orderBy($idColumn)->chunk(200, function ($rows) use ($idColumn, $skipWhen, &$stats) {
                foreach ($rows as $row) {
                    if ($skipWhen($row)) {
                        $stats['skipped']++;
                    }
                }
            });

            return $stats;
        }

        DB::transaction(function () use ($replaceExisting, $stagingIds, $stagingTable, $targetTable, $idColumn, $mapper, $skipWhen, &$stats) {
            if ($replaceExisting) {
                $stats['removed'] = DB::table($targetTable)->whereNotIn('id', $stagingIds)->delete();
            }

            $now = now();

            DB::table($stagingTable)->orderBy($idColumn)->chunk(100, function ($rows) use ($targetTable, $idColumn, $mapper, $skipWhen, $now, &$stats) {
                foreach ($rows as $row) {
                    if ($skipWhen($row)) {
                        $stats['skipped']++;

                        continue;
                    }

                    $id = (int) $row->{$idColumn};
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

    private function parseAmount(mixed $value): ?float
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return null;
        }

        if (preg_match('/^[^\d]*(\d{1,10}(?:\.\d{1,2})?)/', $raw, $matches)) {
            $amount = round((float) $matches[1], 2);

            return $amount > 0 ? $amount : null;
        }

        $clean = preg_replace('/[^\d.]/', '', $raw);
        if ($clean === '' || strlen($clean) > 12 || ! is_numeric($clean)) {
            return null;
        }

        $amount = round((float) $clean, 2);

        return $amount > 0 && $amount <= 9999999999.99 ? $amount : null;
    }

    private function normalizePaymentMode(mixed $value): string
    {
        $mode = strtolower(trim((string) $value));

        return in_array($mode, ['cash', 'cheque', 'online'], true) ? $mode : 'cash';
    }

    private function nullableString(mixed $value): ?string
    {
        $raw = trim((string) $value);

        return $raw === '' ? null : $raw;
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

    private function insertParsedExpenseRows(string $insertStatement): int
    {
        $inserted = 0;

        foreach ($this->parseInsertValueTuples($insertStatement) as $tuple) {
            if (count($tuple) < 12) {
                continue;
            }

            $expenseId = (int) ($tuple[0] ?? 0);
            if ($expenseId <= 0) {
                continue;
            }

            DB::table('legacy_tbl_expensive')->updateOrInsert(
                ['expense_id' => $expenseId],
                [
                    'expense_cat_id' => (int) ($tuple[1] ?? 0),
                    'customer_name' => (string) ($tuple[2] ?? ''),
                    'expense_date' => (string) ($tuple[3] ?? ''),
                    'expense_amount' => (string) ($tuple[4] ?? '0'),
                    'payment_mode' => (string) ($tuple[5] ?? 'cash'),
                    'cheque_no' => (string) ($tuple[6] ?? ''),
                    'bank_name' => (string) ($tuple[7] ?? ''),
                    'note' => (string) ($tuple[8] ?? ''),
                    'voucher' => (string) ($tuple[9] ?? ''),
                    'expensive_month' => (string) ($tuple[10] ?? ''),
                    'expensive_year' => (string) ($tuple[11] ?? ''),
                ],
            );

            $inserted++;
        }

        return $inserted;
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

            if ($closed && count($fields) === 12) {
                $tuples[] = $fields;
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

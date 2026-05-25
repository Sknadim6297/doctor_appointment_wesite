<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LegacyJobImportService
{
    public function loadSqlFile(string $path, bool $truncateStaging = true): int
    {
        if (! is_file($path)) {
            throw new \InvalidArgumentException("SQL file not found: {$path}");
        }

        if (! Schema::hasTable('legacy_tbl_job')) {
            throw new \RuntimeException('Run migrations first (legacy_tbl_job table missing).');
        }

        $sql = file_get_contents($path);
        if ($sql === false || trim($sql) === '') {
            throw new \InvalidArgumentException('SQL file is empty or unreadable.');
        }

        $sql = str_replace('`tbl_job`', '`legacy_tbl_job`', $sql);
        $sql = preg_replace('/CREATE\s+TABLE\s+`?(?:legacy_tbl_job|tbl_job)`?.*?;\s*/is', '', $sql) ?? $sql;
        $sql = preg_replace('/ALTER\s+TABLE\s+`?(?:legacy_tbl_job|tbl_job)`?.*?;\s*/is', '', $sql) ?? $sql;

        if ($truncateStaging) {
            DB::table('legacy_tbl_job')->truncate();
        }

        $inserted = 0;
        foreach ($this->splitSqlStatements($sql) as $statement) {
            $statement = $this->stripSqlComments($statement);
            if ($statement === '' || ! preg_match('/INSERT\s+INTO\s+`?legacy_tbl_job`?/i', $statement)) {
                continue;
            }
            DB::unprepared($statement);
            $inserted += substr_count($statement, '),(') + 1;
        }

        return $inserted;
    }

    /**
     * @return array{created:int,updated:int,removed:int,skipped:int}
     */
    public function syncApplications(bool $replaceExisting = false, bool $dryRun = false): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'removed' => 0, 'skipped' => 0];
        $ids = DB::table('legacy_tbl_job')->orderBy('id')->pluck('id')->map(fn ($id) => (int) $id)->all();

        if ($ids === []) {
            return $stats;
        }

        if ($dryRun) {
            foreach ($ids as $id) {
                if (DB::table('job_applications')->where('id', $id)->exists()) {
                    $stats['updated']++;
                } else {
                    $stats['created']++;
                }
            }

            return $stats;
        }

        DB::transaction(function () use ($replaceExisting, $ids, &$stats) {
            if ($replaceExisting) {
                $stats['removed'] = DB::table('job_applications')->whereNotIn('id', $ids)->delete();
            }

            $now = now();
            foreach (DB::table('legacy_tbl_job')->orderBy('id')->get() as $row) {
                $id = (int) $row->id;
                $name = trim((string) ($row->name ?? ''));
                if ($id <= 0 || $name === '') {
                    $stats['skipped']++;

                    continue;
                }

                $payload = [
                    'name' => $name,
                    'email' => $this->nullableString($row->email ?? null),
                    'mobile' => $this->nullableString($row->mobile ?? null),
                    'salary' => $this->parseDecimal($row->salary ?? 0),
                    'document' => $this->nullableString($row->document ?? null),
                    'applied_at' => $this->parseDateTime($row->created_on ?? null),
                    'updated_at' => $now,
                ];

                if (DB::table('job_applications')->where('id', $id)->exists()) {
                    DB::table('job_applications')->where('id', $id)->update($payload);
                    $stats['updated']++;
                } else {
                    DB::table('job_applications')->insert(array_merge($payload, [
                        'id' => $id,
                        'created_at' => $now,
                    ]));
                    $stats['created']++;
                }
            }
        });

        $this->resetAutoIncrement('job_applications');

        return $stats;
    }

    public function stagingRowCount(): int
    {
        return Schema::hasTable('legacy_tbl_job')
            ? (int) DB::table('legacy_tbl_job')->count()
            : 0;
    }

    private function parseDecimal(mixed $value): float
    {
        return is_numeric($value) ? round((float) $value, 2) : 0.0;
    }

    private function parseDateTime(mixed $value): ?string
    {
        $raw = trim((string) $value);
        if ($raw === '' || $raw === '0000-00-00 00:00:00') {
            return null;
        }

        return $raw;
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
        if (Schema::getConnection()->getDriverName() === 'mysql') {
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

        return array_values(array_filter(array_map(static fn (string $part): string => rtrim(trim($part), ';'), $parts)));
    }

    private function stripSqlComments(string $sql): string
    {
        return trim(preg_replace('/^--.*$/m', '', $sql) ?? $sql);
    }
}

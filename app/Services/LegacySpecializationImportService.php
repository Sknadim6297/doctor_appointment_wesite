<?php

namespace App\Services;

use App\Models\Specialization;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LegacySpecializationImportService
{
    public function loadSqlFile(string $path, bool $truncateStaging = true): int
    {
        if (!is_file($path)) {
            throw new \InvalidArgumentException("SQL file not found: {$path}");
        }

        if (!Schema::hasTable('legacy_specialization')) {
            throw new \RuntimeException('Run migrations first (legacy_specialization table missing).');
        }

        $sql = file_get_contents($path);

        if ($sql === false || trim($sql) === '') {
            throw new \InvalidArgumentException('SQL file is empty or unreadable.');
        }

        $sql = str_replace('`specialization`', '`legacy_specialization`', $sql);
        $sql = preg_replace('/CREATE\s+TABLE\s+`?(?:legacy_specialization|specialization)`?.*?;\s*/is', '', $sql) ?? $sql;
        $sql = preg_replace('/ALTER\s+TABLE\s+`?(?:legacy_specialization|specialization)`?.*?;\s*/is', '', $sql) ?? $sql;

        if ($truncateStaging) {
            DB::table('legacy_specialization')->truncate();
        }

        $statements = $this->splitSqlStatements($sql);
        $inserted = 0;

        foreach ($statements as $statement) {
            $statement = $this->stripSqlComments($statement);

            if ($statement === '' || !$this->isLegacySpecializationInsert($statement)) {
                continue;
            }

            try {
                DB::unprepared($statement);
                $inserted += $this->countInsertRows($statement);
            } catch (\Throwable $exception) {
                throw new \RuntimeException(
                    'Failed executing legacy_specialization INSERT: ' . $exception->getMessage(),
                    (int) $exception->getCode(),
                    $exception
                );
            }
        }

        return $inserted;
    }

    /**
     * @return array{created:int,updated:int,removed:int}
     */
    public function syncToSpecializations(bool $replaceExisting = false, bool $dryRun = false): array
    {
        if (!Schema::hasTable('legacy_specialization')) {
            throw new \RuntimeException('legacy_specialization table missing.');
        }

        $stats = ['created' => 0, 'updated' => 0, 'removed' => 0];
        $stagingIds = DB::table('legacy_specialization')->orderBy('id')->pluck('id')->map(fn ($id) => (int) $id)->all();

        if ($stagingIds === []) {
            return $stats;
        }

        if ($dryRun) {
            foreach ($stagingIds as $id) {
                if (Specialization::query()->whereKey($id)->exists()) {
                    $stats['updated']++;
                } else {
                    $stats['created']++;
                }
            }

            if ($replaceExisting) {
                $stats['removed'] = Specialization::query()->whereNotIn('id', $stagingIds)->count();
            }

            return $stats;
        }

        DB::transaction(function () use ($replaceExisting, $stagingIds, &$stats) {
            if ($replaceExisting) {
                $stats['removed'] = Specialization::query()->whereNotIn('id', $stagingIds)->delete();
            }

            $now = now();

            DB::table('legacy_specialization')->orderBy('id')->chunk(100, function ($rows) use ($now, &$stats) {
                foreach ($rows as $row) {
                    $id = (int) $row->id;
                    $name = $this->normalizeRoleName($row->role);
                    $legacyUserType = (int) ($row->user_type ?? 2);

                    $payload = [
                        'name' => $name,
                        'legacy_user_type' => $legacyUserType,
                        'updated_at' => $now,
                    ];

                    if (Specialization::query()->whereKey($id)->exists()) {
                        Specialization::query()->whereKey($id)->update($payload);
                        $stats['updated']++;
                    } else {
                        DB::table('specializations')->insert(array_merge($payload, [
                            'id' => $id,
                            'created_at' => $now,
                        ]));
                        $stats['created']++;
                    }
                }
            });
        });

        $this->resetSpecializationsAutoIncrement();

        return $stats;
    }

    public function stagingRowCount(): int
    {
        if (!Schema::hasTable('legacy_specialization')) {
            return 0;
        }

        return (int) DB::table('legacy_specialization')->count();
    }

    /**
     * Legacy doctors store specialization id in tbl_user.role_id.
     */
    public function resolveLegacySpecializationId(mixed $legacyId): ?int
    {
        $legacyId = is_numeric($legacyId) ? (int) $legacyId : null;

        if (!$legacyId || $legacyId <= 0) {
            return null;
        }

        return Specialization::query()->whereKey($legacyId)->exists() ? $legacyId : null;
    }

    private function normalizeRoleName(mixed $value): string
    {
        return trim((string) $value);
    }

    private function resetSpecializationsAutoIncrement(): void
    {
        $maxId = (int) DB::table('specializations')->max('id');
        $next = max($maxId + 1, 1);

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('DELETE FROM sqlite_sequence WHERE name = "specializations"');
            DB::statement('INSERT INTO sqlite_sequence (name, seq) VALUES ("specializations", ' . $maxId . ')');
        } else {
            DB::statement("ALTER TABLE specializations AUTO_INCREMENT = {$next}");
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

    private function isLegacySpecializationInsert(string $statement): bool
    {
        return (bool) preg_match('/INSERT\s+INTO\s+`?legacy_specialization`?/i', $statement);
    }
}

<?php

namespace App\Services;

use App\Models\DoctorPost;
use App\Models\Enrollment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use stdClass;

class LegacyDoctorPostImportService
{
    public function loadSqlFile(string $path, bool $truncateStaging = true): int
    {
        if (!is_file($path)) {
            throw new \InvalidArgumentException("SQL file not found: {$path}");
        }

        if (!Schema::hasTable('legacy_tbl_doctor_post')) {
            throw new \RuntimeException('Run migrations first (legacy_tbl_doctor_post table missing).');
        }

        $sql = file_get_contents($path);

        if ($sql === false || trim($sql) === '') {
            throw new \InvalidArgumentException('SQL file is empty or unreadable.');
        }

        $sql = str_replace('`tbl_doctor_post`', '`legacy_tbl_doctor_post`', $sql);
        $sql = preg_replace('/CREATE\s+TABLE\s+`?(?:legacy_tbl_doctor_post|tbl_doctor_post)`?.*?;\s*/is', '', $sql) ?? $sql;
        $sql = preg_replace('/ALTER\s+TABLE\s+`?(?:legacy_tbl_doctor_post|tbl_doctor_post)`?.*?;\s*/is', '', $sql) ?? $sql;
        $sql = preg_replace("/'0000-00-00'/", 'NULL', $sql) ?? $sql;

        if ($truncateStaging) {
            DB::table('legacy_tbl_doctor_post')->truncate();
        }

        $statements = $this->splitSqlStatements($sql);
        $inserted = 0;

        foreach ($statements as $statement) {
            $statement = $this->stripSqlComments($statement);

            if ($statement === '' || !$this->isLegacyDoctorPostInsert($statement)) {
                continue;
            }

            try {
                DB::unprepared($statement);
                $inserted += $this->countInsertRows($statement);
            } catch (\Throwable $exception) {
                throw new \RuntimeException(
                    'Failed executing legacy_tbl_doctor_post INSERT: ' . $exception->getMessage(),
                    (int) $exception->getCode(),
                    $exception
                );
            }
        }

        return $inserted;
    }

    /**
     * @return array{created:int,updated:int,skipped:int,linked:int,errors:array<int,string>}
     */
    public function syncFromStaging(bool $dryRun = false): array
    {
        if (!Schema::hasTable('legacy_tbl_doctor_post')) {
            throw new \RuntimeException('legacy_tbl_doctor_post table missing.');
        }

        if (!Schema::hasColumn('doctor_posts', 'legacy_post_id')) {
            throw new \RuntimeException('doctor_posts.legacy_post_id column missing. Run migrations.');
        }

        $defaultCreatedBy = $this->defaultAdminUserId();
        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'linked' => 0, 'errors' => []];

        DB::table('legacy_tbl_doctor_post')->orderBy('post_id')->chunk(200, function ($rows) use ($dryRun, $defaultCreatedBy, &$stats) {
            foreach ($rows as $row) {
                try {
                    $legacyPostId = (int) $row->post_id;

                    if ($legacyPostId <= 0) {
                        $stats['skipped']++;

                        continue;
                    }

                    $payload = $this->mapLegacyRow($row, $defaultCreatedBy);

                    if ($payload === null) {
                        $stats['skipped']++;

                        continue;
                    }

                    if ($payload['enrollment_id'] !== null) {
                        $stats['linked']++;
                    }

                    if ($dryRun) {
                        DoctorPost::query()->where('legacy_post_id', $legacyPostId)->exists()
                            ? $stats['updated']++
                            : $stats['created']++;

                        continue;
                    }

                    $existing = DoctorPost::query()->where('legacy_post_id', $legacyPostId)->first();

                    if ($existing) {
                        $existing->fill($payload);
                        $existing->save();
                        $stats['updated']++;
                    } else {
                        DoctorPost::query()->create($payload);
                        $stats['created']++;
                    }
                } catch (\Throwable $exception) {
                    $stats['errors'][(int) $row->post_id] = $exception->getMessage();
                }
            }
        });

        return $stats;
    }

    public function stagingRowCount(): int
    {
        if (!Schema::hasTable('legacy_tbl_doctor_post')) {
            return 0;
        }

        return (int) DB::table('legacy_tbl_doctor_post')->count();
    }

    private function mapLegacyRow(stdClass $row, ?int $defaultCreatedBy): ?array
    {
        $legacyPostId = (int) $row->post_id;
        $legacyDoctorId = is_numeric($row->doctor_id) ? (int) $row->doctor_id : null;

        if ($legacyPostId <= 0) {
            return null;
        }

        $enrollment = $legacyDoctorId && $legacyDoctorId > 0
            ? Enrollment::query()->where('legacy_user_id', $legacyDoctorId)->first()
            : null;

        $remark = $this->stringOrNull($row->remark)
            ?? $this->stringOrNull($row->post_document_name)
            ?? 'Legacy post record';

        $createdAt = $this->parseDate($row->created_date);
        $updatedAt = $this->parseDate($row->edited_date) ?? $createdAt;

        $payload = [
            'legacy_post_id' => $legacyPostId,
            'enrollment_id' => $enrollment?->id,
            'doctor_name' => $enrollment?->doctor_name ?? $this->stringOrNull($row->post_document_name),
            'post_doc_date' => $this->parseDate($row->post_date),
            'post_doc_consignment_no' => $this->stringOrNull($row->consignment_no),
            'post_doc_by' => $this->stringOrNull($row->post_by),
            'post_doc_recieved_date' => $this->parseDate($row->recieved_date),
            'post_doc_recieved_by' => $this->stringOrNull($row->recieved_by),
            'post_doc_remark' => $remark,
            'tracking_link' => $this->normalizeTrackingLink($row->tracking_link),
            'post_doc_file' => null,
            'created_by' => $this->resolveLegacyUserId($row->created_by) ?? $defaultCreatedBy ?? null,
        ];

        if ($createdAt !== null) {
            $payload['created_at'] = Carbon::parse($createdAt)->startOfDay();
        }

        if ($updatedAt !== null) {
            $payload['updated_at'] = Carbon::parse($updatedAt)->startOfDay();
        }

        return $payload;
    }

    private function normalizeTrackingLink(mixed $value): ?string
    {
        $value = $this->stringOrNull($value);

        if ($value === null) {
            return null;
        }

        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            return null;
        }

        return $value;
    }

    private function parseDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '' || $value === '0000-00-00' || str_starts_with($value, '1970-01-01')) {
            return null;
        }

        try {
            $parsed = Carbon::parse($value);
            $year = (int) $parsed->format('Y');

            if ($year < 1900 || $year > ((int) date('Y') + 2)) {
                return null;
            }

            return $parsed->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveLegacyUserId(mixed $legacyId): ?int
    {
        $legacyId = is_numeric($legacyId) ? (int) $legacyId : null;

        if (!$legacyId || $legacyId <= 0) {
            return null;
        }

        if (!Schema::hasColumn('users', 'legacy_user_id')) {
            return null;
        }

        return User::query()->where('legacy_user_id', $legacyId)->value('id');
    }

    private function defaultAdminUserId(): ?int
    {
        $id = User::query()
            ->whereIn('role', ['super_admin', 'admin'])
            ->orderBy('id')
            ->value('id');

        if ($id) {
            return (int) $id;
        }

        $fallback = User::query()->orderBy('id')->value('id');

        return $fallback ? (int) $fallback : null;
    }

    private function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
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

    private function isLegacyDoctorPostInsert(string $statement): bool
    {
        return (bool) preg_match('/INSERT\s+INTO\s+`?legacy_tbl_doctor_post`?/i', $statement);
    }
}

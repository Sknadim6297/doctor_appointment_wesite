<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\User;
use App\Support\EnrollmentWorkflow;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use stdClass;

class LegacyCallSheetImportService
{
    private const INVALID_EMAIL_MARKERS = ['n.a', 'n.a.', 'na', 'n/a'];

    public function __construct(
        private readonly LegacySpecializationImportService $specializationImport
    ) {
    }

    public function loadSqlFile(string $path, bool $truncateStaging = true): int
    {
        if (!is_file($path)) {
            throw new \InvalidArgumentException("SQL file not found: {$path}");
        }

        if (!Schema::hasTable('legacy_tbl_call_sheet')) {
            throw new \RuntimeException('Run migrations first (legacy_tbl_call_sheet table missing).');
        }

        $sql = file_get_contents($path);

        if ($sql === false || trim($sql) === '') {
            throw new \InvalidArgumentException('SQL file is empty or unreadable.');
        }

        $sql = str_replace('`tbl_call_sheet`', '`legacy_tbl_call_sheet`', $sql);
        $sql = preg_replace('/CREATE\s+TABLE\s+`?(?:legacy_tbl_call_sheet|tbl_call_sheet)`?.*?;\s*/is', '', $sql) ?? $sql;
        $sql = preg_replace('/ALTER\s+TABLE\s+`?(?:legacy_tbl_call_sheet|tbl_call_sheet)`?.*?;\s*/is', '', $sql) ?? $sql;
        $sql = preg_replace("/'0000-00-00'/", 'NULL', $sql) ?? $sql;

        if ($truncateStaging) {
            DB::table('legacy_tbl_call_sheet')->truncate();
        }

        $statements = $this->splitSqlStatements($sql);
        $inserted = 0;

        foreach ($statements as $statement) {
            $statement = $this->stripSqlComments($statement);

            if ($statement === '' || !$this->isLegacyCallSheetInsert($statement)) {
                continue;
            }

            try {
                DB::unprepared($statement);
                $inserted += $this->countInsertRows($statement);
            } catch (\Throwable $exception) {
                throw new \RuntimeException(
                    'Failed executing legacy_tbl_call_sheet INSERT: ' . $exception->getMessage(),
                    (int) $exception->getCode(),
                    $exception
                );
            }
        }

        return $inserted;
    }

    /**
     * @return array{created:int,updated:int,skipped:int,errors:array<int,string>}
     */
    public function syncFromStaging(bool $dryRun = false): array
    {
        if (!Schema::hasTable('legacy_tbl_call_sheet')) {
            throw new \RuntimeException('legacy_tbl_call_sheet table missing.');
        }

        $defaultCreatedBy = $this->defaultAdminUserId();
        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];

        DB::table('legacy_tbl_call_sheet')->orderBy('call_sheet_id')->chunk(100, function ($rows) use ($dryRun, $defaultCreatedBy, &$stats) {
            foreach ($rows as $row) {
                try {
                    $payload = $this->mapLegacyRow($row, $defaultCreatedBy);

                    if ($payload === null) {
                        $stats['skipped']++;

                        continue;
                    }

                    if ($dryRun) {
                        $exists = Enrollment::query()->where('legacy_call_sheet_id', $row->call_sheet_id)->exists();
                        $exists ? $stats['updated']++ : $stats['created']++;

                        continue;
                    }

                    $enrollment = Enrollment::query()
                        ->where('legacy_call_sheet_id', (int) $row->call_sheet_id)
                        ->first();

                    if ($enrollment) {
                        $enrollment->fill($payload);
                        $enrollment->save();
                        $stats['updated']++;
                    } else {
                        Enrollment::query()->create($payload);
                        $stats['created']++;
                    }
                } catch (\Throwable $exception) {
                    $stats['errors'][(int) $row->call_sheet_id] = $exception->getMessage();
                }
            }
        });

        return $stats;
    }

    /**
     * Legacy doctor enrollments must not appear on the marketing call sheet list.
     */
    public function hideLegacyDoctorsFromCallSheet(): int
    {
        if (!Schema::hasColumn('enrollments', 'legacy_user_id')) {
            return 0;
        }

        return Enrollment::query()
            ->whereNotNull('legacy_user_id')
            ->where('hide_from_call_sheet', false)
            ->update(['hide_from_call_sheet' => true]);
    }

    public function stagingRowCount(): int
    {
        if (!Schema::hasTable('legacy_tbl_call_sheet')) {
            return 0;
        }

        return (int) DB::table('legacy_tbl_call_sheet')->count();
    }

    private function mapLegacyRow(stdClass $row, int $defaultCreatedBy): ?array
    {
        $doctorName = $this->normalizeDoctorName($row->name);

        if ($doctorName === null) {
            return null;
        }

        $specializationId = $this->parseSpecializationId($row->specialization);
        $createdAt = $this->legacyDateTime($row->created_date, $row->month, $row->year) ?? now();
        $updatedAt = $this->legacyDateTime($row->edited_date) ?? $createdAt;
        $createdBy = $this->resolveLegacyUserId($row->created_by) ?? $defaultCreatedBy;

        $payload = [
            'legacy_call_sheet_id' => (int) $row->call_sheet_id,
            'doctor_name' => $doctorName,
            'doctor_email' => $this->normalizeEmail($row->email),
            'mobile1' => $this->normalizePhone($row->phone),
            'specialization_id' => $specializationId,
            'call_sheet_specialization_ids' => $specializationId ? [$specializationId] : null,
            'call_sheet_card_slug' => $this->stringOrNull($row->card),
            'call_sheet_month' => $this->stringOrNull($row->month),
            'call_sheet_year' => $this->stringOrNull($row->year),
            'hide_from_call_sheet' => false,
            'status' => 'draft',
            'workflow_status' => EnrollmentWorkflow::DRAFT,
            'current_step' => 1,
            'is_step_incomplete' => true,
            'completed_steps' => [],
            'workflow_completed_at' => null,
            'created_by' => $createdBy,
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ];

        return $payload;
    }

    private function parseSpecializationId(mixed $value): ?int
    {
        $value = trim((string) ($value ?? ''));

        if ($value === '' || !ctype_digit($value)) {
            return null;
        }

        return $this->specializationImport->resolveLegacySpecializationId((int) $value);
    }

    private function normalizeDoctorName(mixed $value): ?string
    {
        $value = $this->stringOrNull($value);

        return $value === null ? null : Str::upper($value);
    }

    private function normalizeEmail(mixed $value): ?string
    {
        $email = $this->stringOrNull($value);

        if ($email === null) {
            return null;
        }

        $marker = strtolower(str_replace([' ', '.'], '', $email));

        if (in_array($marker, self::INVALID_EMAIL_MARKERS, true)) {
            return null;
        }

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? strtolower($email) : null;
    }

    private function normalizePhone(mixed $value): ?string
    {
        $phone = $this->stringOrNull($value);

        if ($phone === null) {
            return null;
        }

        return Str::limit($phone, 20, '');
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

    private function defaultAdminUserId(): int
    {
        $id = User::query()
            ->whereIn('role', ['super_admin', 'admin'])
            ->orderBy('id')
            ->value('id');

        return (int) ($id ?? User::query()->orderBy('id')->value('id') ?? 1);
    }

    private function legacyDateTime(mixed $value, mixed $month = null, mixed $year = null): ?Carbon
    {
        $value = $this->stringOrNull($value);

        if ($value !== null && !str_starts_with($value, '0000-00-00')) {
            try {
                return Carbon::parse($value)->startOfDay();
            } catch (\Throwable) {
                // fall through to month/year
            }
        }

        $monthName = $this->stringOrNull($month);
        $yearValue = $this->stringOrNull($year);

        if ($monthName === null || $yearValue === null || !ctype_digit($yearValue)) {
            return null;
        }

        try {
            return Carbon::parse('1 ' . $monthName . ' ' . $yearValue)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
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

    private function isLegacyCallSheetInsert(string $statement): bool
    {
        return (bool) preg_match('/INSERT\s+INTO\s+`?legacy_tbl_call_sheet`?/i', $statement);
    }
}

<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\PolicyReceipt;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use stdClass;

class LegacyDoctorPolicyImportService
{
    public function loadSqlFile(string $path, bool $truncateStaging = true): int
    {
        if (!is_file($path)) {
            throw new \InvalidArgumentException("SQL file not found: {$path}");
        }

        if (!Schema::hasTable('legacy_tbl_doctor_policy')) {
            throw new \RuntimeException('Run migrations first (legacy_tbl_doctor_policy table missing).');
        }

        $sql = file_get_contents($path);

        if ($sql === false || trim($sql) === '') {
            throw new \InvalidArgumentException('SQL file is empty or unreadable.');
        }

        $sql = str_replace('`tbl_doctor_policy`', '`legacy_tbl_doctor_policy`', $sql);
        $sql = preg_replace('/CREATE\s+TABLE\s+`?(?:legacy_tbl_doctor_policy|tbl_doctor_policy)`?.*?;\s*/is', '', $sql) ?? $sql;
        $sql = preg_replace('/ALTER\s+TABLE\s+`?(?:legacy_tbl_doctor_policy|tbl_doctor_policy)`?.*?;\s*/is', '', $sql) ?? $sql;

        if ($truncateStaging) {
            DB::table('legacy_tbl_doctor_policy')->truncate();
        }

        $statements = $this->splitSqlStatements($sql);
        $inserted = 0;

        foreach ($statements as $statement) {
            $statement = $this->stripSqlComments($statement);

            if ($statement === '' || !$this->isLegacyDoctorPolicyInsert($statement)) {
                continue;
            }

            try {
                DB::unprepared($statement);
                $inserted += $this->countInsertRows($statement);
            } catch (\Throwable $exception) {
                throw new \RuntimeException(
                    'Failed executing legacy_tbl_doctor_policy INSERT: ' . $exception->getMessage(),
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
        if (!Schema::hasTable('legacy_tbl_doctor_policy')) {
            throw new \RuntimeException('legacy_tbl_doctor_policy table missing.');
        }

        if (!Schema::hasColumn('policy_receipts', 'legacy_policy_id')) {
            throw new \RuntimeException('policy_receipts.legacy_policy_id column missing. Run migrations.');
        }

        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'linked' => 0, 'errors' => []];

        DB::table('legacy_tbl_doctor_policy')->orderBy('policy_id')->chunk(300, function ($rows) use ($dryRun, &$stats) {
            foreach ($rows as $row) {
                try {
                    $legacyPolicyId = (int) $row->policy_id;

                    if ($legacyPolicyId <= 0) {
                        $stats['skipped']++;

                        continue;
                    }

                    $payload = $this->mapLegacyRow($row);

                    if ($payload === null) {
                        $stats['skipped']++;

                        continue;
                    }

                    if ($payload['enrollment_id'] !== null) {
                        $stats['linked']++;
                    }

                    if ($dryRun) {
                        PolicyReceipt::query()->where('legacy_policy_id', $legacyPolicyId)->exists()
                            ? $stats['updated']++
                            : $stats['created']++;

                        continue;
                    }

                    $existing = PolicyReceipt::query()->where('legacy_policy_id', $legacyPolicyId)->first();

                    if ($existing) {
                        $existing->fill($payload);
                        $existing->save();
                        $stats['updated']++;
                    } else {
                        PolicyReceipt::query()->create($payload);
                        $stats['created']++;
                    }
                } catch (\Throwable $exception) {
                    $stats['errors'][(int) $row->policy_id] = $exception->getMessage();
                }
            }
        });

        return $stats;
    }

    public function stagingRowCount(): int
    {
        if (!Schema::hasTable('legacy_tbl_doctor_policy')) {
            return 0;
        }

        return (int) DB::table('legacy_tbl_doctor_policy')->count();
    }

    private function mapLegacyRow(stdClass $row): ?array
    {
        $legacyPolicyId = (int) $row->policy_id;

        if ($legacyPolicyId <= 0) {
            return null;
        }

        $policyNo = $this->normalizePolicyNo($row->policy_no);

        if ($policyNo === null) {
            return null;
        }

        $legacyDoctorId = is_numeric($row->doctor_id) ? (int) $row->doctor_id : null;
        $enrollment = $legacyDoctorId && $legacyDoctorId > 0
            ? Enrollment::query()->where('legacy_user_id', $legacyDoctorId)->first()
            : null;

        $year = $this->parsePolicyYear($row->year);
        $receiveDate = $this->receiveDateFromYear($year);

        return [
            'legacy_policy_id' => $legacyPolicyId,
            'policy_no' => $policyNo,
            'enrollment_id' => $enrollment?->id,
            'doctor_name' => $enrollment?->doctor_name,
            'receive_date' => $receiveDate,
            'last_renewed_date' => $receiveDate,
            'policy_start_date' => null,
            'policy_end_date' => null,
            'policy_file' => null,
            'workflow_status' => PolicyReceipt::STATUS_COMPLETED,
        ];
    }

    private function normalizePolicyNo(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = preg_replace('/\s+/u', ' ', trim((string) $value)) ?? '';

        if ($value === '') {
            return null;
        }

        return Str::limit($value, 255, '');
    }

    private function parsePolicyYear(mixed $value): ?int
    {
        $value = $this->stringOrNull($value);

        if ($value === null) {
            return null;
        }

        if (ctype_digit($value)) {
            $year = (int) $value;

            return $this->isValidPolicyYear($year) ? $year : null;
        }

        if (preg_match('/\b(19|20)\d{2}\b/', $value, $matches)) {
            $year = (int) $matches[0];

            return $this->isValidPolicyYear($year) ? $year : null;
        }

        return null;
    }

    private function receiveDateFromYear(?int $year): ?string
    {
        if ($year === null) {
            return null;
        }

        return Carbon::create($year, 1, 1)->toDateString();
    }

    private function isValidPolicyYear(int $year): bool
    {
        return $year >= 1900 && $year <= ((int) date('Y') + 2);
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

    private function isLegacyDoctorPolicyInsert(string $statement): bool
    {
        return (bool) preg_match('/INSERT\s+INTO\s+`?legacy_tbl_doctor_policy`?/i', $statement);
    }
}

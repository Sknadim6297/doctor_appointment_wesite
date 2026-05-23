<?php

namespace App\Services;

use App\Models\Enrollment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use stdClass;

class LegacyDoctorDetailsImportService
{
    public function __construct(
        private readonly LegacySpecializationImportService $specializationImport
    ) {
    }

    public function loadSqlFile(string $path, bool $truncateStaging = true): int
    {
        if (!is_file($path)) {
            throw new \InvalidArgumentException("SQL file not found: {$path}");
        }

        if (!Schema::hasTable('legacy_tbl_doctor_details')) {
            throw new \RuntimeException('Run migrations first (legacy_tbl_doctor_details table missing).');
        }

        $sql = file_get_contents($path);

        if ($sql === false || trim($sql) === '') {
            throw new \InvalidArgumentException('SQL file is empty or unreadable.');
        }

        $sql = str_replace('`tbl_doctor_details`', '`legacy_tbl_doctor_details`', $sql);
        $sql = preg_replace('/CREATE\s+TABLE\s+`?(?:legacy_tbl_doctor_details|tbl_doctor_details)`?.*?;\s*/is', '', $sql) ?? $sql;
        $sql = preg_replace('/ALTER\s+TABLE\s+`?(?:legacy_tbl_doctor_details|tbl_doctor_details)`?.*?;\s*/is', '', $sql) ?? $sql;

        if ($truncateStaging) {
            DB::table('legacy_tbl_doctor_details')->truncate();
        }

        $statements = $this->splitSqlStatements($sql);
        $inserted = 0;

        foreach ($statements as $statement) {
            $statement = $this->stripSqlComments($statement);

            if ($statement === '' || !$this->isLegacyDoctorDetailsInsert($statement)) {
                continue;
            }

            try {
                DB::unprepared($statement);
                $inserted += $this->countInsertRows($statement);
            } catch (\Throwable $exception) {
                throw new \RuntimeException(
                    'Failed executing legacy_tbl_doctor_details INSERT: ' . $exception->getMessage(),
                    (int) $exception->getCode(),
                    $exception
                );
            }
        }

        return $inserted;
    }

    /**
     * @return array{updated:int,skipped:int,errors:array<int,string>}
     */
    public function syncDetailsToEnrollments(bool $dryRun = false): array
    {
        if (!Schema::hasTable('legacy_tbl_doctor_details')) {
            throw new \RuntimeException('legacy_tbl_doctor_details table missing.');
        }

        $stats = ['updated' => 0, 'skipped' => 0, 'errors' => []];

        DB::table('legacy_tbl_doctor_details')->orderBy('doctor_detais_id')->chunk(100, function ($rows) use ($dryRun, &$stats) {
            foreach ($rows as $row) {
                try {
                    $legacyUserId = is_numeric($row->user_id) ? (int) $row->user_id : null;

                    if (!$legacyUserId || $legacyUserId <= 0) {
                        $stats['skipped']++;

                        continue;
                    }

                    $enrollment = Enrollment::query()
                        ->where('legacy_user_id', $legacyUserId)
                        ->first();

                    if (!$enrollment) {
                        $stats['skipped']++;

                        continue;
                    }

                    $payload = $this->mapLegacyRow($row);

                    if ($payload === []) {
                        $stats['skipped']++;

                        continue;
                    }

                    if ($dryRun) {
                        $stats['updated']++;

                        continue;
                    }

                    $enrollment->fill($payload);
                    $enrollment->save();
                    $stats['updated']++;
                } catch (\Throwable $exception) {
                    $stats['errors'][(int) $row->doctor_detais_id] = $exception->getMessage();
                }
            }
        });

        return $stats;
    }

    public function stagingRowCount(): int
    {
        if (!Schema::hasTable('legacy_tbl_doctor_details')) {
            return 0;
        }

        return (int) DB::table('legacy_tbl_doctor_details')->count();
    }

    /**
     * @return array<string, mixed>
     */
    private function mapLegacyRow(stdClass $row): array
    {
        $payload = [];

        [$qualification, $qualificationYear] = $this->parseQualificationFields(
            $this->stringOrNull($row->qualification),
            $this->stringOrNull($row->qualification_year)
        );

        if ($qualification !== null) {
            $payload['qualification'] = $qualification;
        }

        if ($qualificationYear !== null) {
            $payload['qualification_year'] = $qualificationYear;
        }

        $medicalRegNo = $this->stringOrNull($row->medical_reg_no);

        if ($medicalRegNo !== null) {
            $payload['medical_registration_no'] = $medicalRegNo;
        }

        $yearOfReg = $this->parseYearOfReg($row->medical_reg_year);

        if ($yearOfReg !== null) {
            $payload['year_of_reg'] = $yearOfReg;
        }

        $clinicAddress = $this->stringOrNull($row->clinic_address);

        if ($clinicAddress !== null) {
            $payload['clinic_address'] = $clinicAddress;
        }

        $agentName = $this->stringOrNull($row->agent_name);

        if ($agentName !== null) {
            $payload['agent_name'] = $agentName;
        }

        $agentPhone = $this->normalizePhone($row->agent_phone);

        if ($agentPhone !== null) {
            $payload['agent_phone_no'] = $agentPhone;
        }

        $specializationId = $this->specializationImport->resolveLegacySpecializationId($row->specilality_id);

        if ($specializationId !== null) {
            $payload['specialization_id'] = $specializationId;
        }

        return $payload;
    }

    /**
     * @return array{0:?array<int, string>, 1:?array<int, string>}
     */
    private function parseQualificationFields(?string $qualification, ?string $years): array
    {
        $names = $this->splitLegacyCommaList($qualification);
        $yearList = $this->splitLegacyCommaList($years);

        $qualificationOut = $names !== [] ? $names : null;
        $yearsOut = $yearList !== [] ? $yearList : null;

        return [$qualificationOut, $yearsOut];
    }

    /**
     * @return array<int, string>
     */
    private function splitLegacyCommaList(?string $value): array
    {
        if ($value === null) {
            return [];
        }

        $parts = array_map(static fn (string $part): string => trim($part), explode(',', $value));

        return array_values(array_filter($parts, static fn (string $part): bool => $part !== ''));
    }

    private function parseYearOfReg(mixed $value): ?string
    {
        $value = $this->stringOrNull($value);

        if ($value === null) {
            return null;
        }

        if (preg_match('/\b(19|20)\d{2}\b/', $value, $matches)) {
            $year = (int) $matches[0];

            if ($year >= 1900 && $year <= (int) date('Y') + 1) {
                return (string) $year;
            }
        }

        if (is_numeric($value)) {
            $year = (int) $value;

            if ($year >= 1900 && $year <= (int) date('Y') + 1) {
                return (string) $year;
            }
        }

        return null;
    }

    private function normalizePhone(mixed $value): ?string
    {
        $value = $this->stringOrNull($value);

        if ($value === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value) ?? '';

        return $digits !== '' ? $digits : $value;
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

    private function isLegacyDoctorDetailsInsert(string $statement): bool
    {
        return (bool) preg_match('/INSERT\s+INTO\s+`?legacy_tbl_doctor_details`?/i', $statement);
    }
}

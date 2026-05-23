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

class LegacyDoctorImportService
{
    private const DOCTOR_USER_TYPE_ID = 3;

    private const PLACEHOLDER_DOCUMENT_VALUES = [
        '0',
        'n.a',
        'n.a.',
        'na',
        'not found',
        'not collected',
        'not_found',
    ];

    public function loadSqlFile(string $path, bool $truncateStaging = true): int
    {
        if (!is_file($path)) {
            throw new \InvalidArgumentException("SQL file not found: {$path}");
        }

        if (!Schema::hasTable('legacy_tbl_user')) {
            throw new \RuntimeException('Run migrations first (legacy_tbl_user table missing).');
        }

        $sql = file_get_contents($path);

        if ($sql === false || trim($sql) === '') {
            throw new \InvalidArgumentException('SQL file is empty or unreadable.');
        }

        $sql = str_replace('`tbl_user`', '`legacy_tbl_user`', $sql);
        $sql = preg_replace('/CREATE\s+TABLE\s+`?(?:legacy_tbl_user|tbl_user)`?.*?;\s*/is', '', $sql) ?? $sql;
        $sql = preg_replace('/;\s*this is the all (?:doctors|users) data please insert.*?$/im', ';', $sql) ?? $sql;

        if ($truncateStaging) {
            DB::table('legacy_tbl_user')->truncate();
        }

        $statements = $this->splitSqlStatements($sql);
        $inserted = 0;

        foreach ($statements as $statement) {
            $statement = $this->stripSqlComments($statement);

            if ($statement === '' || !$this->isLegacyUserInsert($statement)) {
                continue;
            }

            try {
                DB::unprepared($statement);
                $inserted += $this->countInsertRows($statement);
            } catch (\Throwable $exception) {
                throw new \RuntimeException(
                    'Failed executing legacy_tbl_user INSERT: ' . $exception->getMessage(),
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
    public function syncDoctorsFromStaging(bool $dryRun = false, bool $onlyActive = false): array
    {
        if (!Schema::hasTable('legacy_tbl_user')) {
            throw new \RuntimeException('legacy_tbl_user table missing.');
        }

        $specializationImport = app(LegacySpecializationImportService::class);
        $defaultCreatedBy = $this->defaultAdminUserId();
        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];

        $query = DB::table('legacy_tbl_user')->where('user_type_id', self::DOCTOR_USER_TYPE_ID);

        if ($onlyActive) {
            $query->where('status', 'active');
        }

        $query->orderBy('id')->chunk(200, function ($rows) use ($dryRun, $defaultCreatedBy, $specializationImport, &$stats) {
            foreach ($rows as $row) {
                try {
                    $payload = $this->mapLegacyRow($row, $defaultCreatedBy, $specializationImport);

                    if ($payload === null) {
                        $stats['skipped']++;

                        continue;
                    }

                    if ($dryRun) {
                        $exists = Enrollment::query()
                            ->where(function ($query) use ($row, $payload) {
                                $query->where('legacy_user_id', $row->id);

                                if (!empty($payload['customer_id_no'])) {
                                    $query->orWhere('customer_id_no', $payload['customer_id_no']);
                                }
                            })
                            ->exists();

                        if ($exists) {
                            $stats['updated']++;
                        } else {
                            $stats['created']++;
                        }

                        continue;
                    }

                    $enrollment = Enrollment::query()
                        ->where('legacy_user_id', $row->id)
                        ->first();

                    if (!$enrollment && !empty($payload['customer_id_no'])) {
                        $enrollment = Enrollment::query()
                            ->where('customer_id_no', $payload['customer_id_no'])
                            ->first();
                    }

                    if ($enrollment) {
                        $enrollment->fill($payload);
                        $enrollment->save();
                        $stats['updated']++;
                    } else {
                        Enrollment::query()->create($payload);
                        $stats['created']++;
                    }
                } catch (\Throwable $exception) {
                    $stats['errors'][$row->id] = $exception->getMessage();
                }
            }
        });

        return $stats;
    }

    public function stagingRowCount(): int
    {
        if (!Schema::hasTable('legacy_tbl_user')) {
            return 0;
        }

        return (int) DB::table('legacy_tbl_user')->where('user_type_id', self::DOCTOR_USER_TYPE_ID)->count();
    }

    private function mapLegacyRow(stdClass $row, int $defaultCreatedBy, LegacySpecializationImportService $specializationImport): ?array
    {
        $customerId = $this->stringOrNull($row->user_unique_id);
        $doctorName = $this->resolveDoctorName($row);

        if ($doctorName === null && $customerId === null) {
            return null;
        }

        if ($doctorName === null) {
            $doctorName = $customerId ?? 'Unknown Doctor';
        }

        if ($customerId === null) {
            $customerId = 'LEGACY-' . $row->id;
        }

        $isActive = strtolower((string) ($row->status ?? '')) === 'active';
        $createdAt = $this->legacyDateTime($row->created_date) ?? now();
        $updatedAt = $this->legacyDateTime($row->edited_date) ?? $createdAt;
        $createdBy = $this->resolveLegacyUserId($row->created_by) ?? $defaultCreatedBy;

        $payload = [
            'legacy_user_id' => (int) $row->id,
            'customer_id_no' => $customerId,
            'doctor_name' => $doctorName,
            'doctor_address' => $this->stringOrNull($row->full_address),
            'clinic_address' => $this->stringOrNull($row->full_address),
            'country' => $this->stringOrNull($row->country_id),
            'state' => $row->state_id !== null ? (string) $row->state_id : null,
            'city' => $row->city_id !== null ? (string) $row->city_id : null,
            'postcode' => $row->pincode !== null ? (string) $row->pincode : null,
            'mobile1' => $this->stringOrNull($row->mobile_no),
            'mobile2' => $this->stringOrNull($row->alt_mobile_no),
            'doctor_email' => $this->normalizeEmail($row->email),
            'dob' => $this->parseDob($row->dob),
            'aadhar_card_no' => $this->normalizeDocumentNo($row->aadhar_card_no),
            'pan_card_no' => $this->normalizeDocumentNo($row->pan_card_no),
            'auto_sms_enabled' => $this->legacyAutoSms($row->auto_sms),
            'bond_to_mail' => strtolower((string) ($row->auto_email_send ?? '')) === 'y',
            'specialization_id' => $specializationImport->resolveLegacySpecializationId($row->role_id),
            'hide_from_call_sheet' => true,
            'created_by' => $createdBy,
            'agent_id' => $this->resolveLegacyUserId($row->edited_by),
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ];

        if ($isActive) {
            $payload['status'] = 'approved';
            $payload['workflow_status'] = EnrollmentWorkflow::COMPLETED;
            $payload['current_step'] = 4;
            $payload['is_step_incomplete'] = false;
            $payload['completed_steps'] = [1, 2, 3, 4];
            $payload['workflow_completed_at'] = $updatedAt;
            $payload['approved_at'] = $updatedAt;
            $payload['approved_by'] = $createdBy;
        } else {
            $payload['status'] = 'draft';
            $payload['workflow_status'] = EnrollmentWorkflow::DRAFT;
            $payload['current_step'] = 1;
            $payload['is_step_incomplete'] = true;
            $payload['completed_steps'] = [];
            $payload['workflow_completed_at'] = null;
        }

        return $payload;
    }

    private function resolveDoctorName(stdClass $row): ?string
    {
        foreach (['full_name', 'first_name'] as $field) {
            $value = $this->stringOrNull($row->{$field} ?? null);

            if ($value !== null) {
                return Str::upper(trim($value));
            }
        }

        return null;
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

        if ($id) {
            return (int) $id;
        }

        return (int) (User::query()->orderBy('id')->value('id') ?? 1);
    }

    private function normalizeDocumentNo(mixed $value): ?string
    {
        $value = $this->stringOrNull($value);

        if ($value === null) {
            return null;
        }

        $normalized = strtolower($value);

        if (in_array($normalized, self::PLACEHOLDER_DOCUMENT_VALUES, true)) {
            return null;
        }

        return $value;
    }

    private function normalizeEmail(mixed $value): ?string
    {
        $email = $this->stringOrNull($value);

        if ($email === null || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return strtolower($email);
    }

    private function legacyAutoSms(mixed $value): bool
    {
        return strtolower(trim((string) $value)) === 'yes';
    }

    private function parseDob(mixed $value): ?string
    {
        $value = $this->stringOrNull($value);

        if ($value === null) {
            return null;
        }

        $formats = ['Y-m-d', 'd-m-Y', 'm-d-Y', 'Y/m/d', 'd/m/Y'];

        foreach ($formats as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $value);

                if ($parsed && $parsed->year >= 1900 && $parsed->year <= now()->year) {
                    return $parsed->format('Y-m-d');
                }
            } catch (\Throwable) {
                continue;
            }
        }

        try {
            $parsed = Carbon::parse($value);

            if ($parsed->year >= 1900 && $parsed->year <= now()->year) {
                return $parsed->format('Y-m-d');
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    private function legacyDateTime(mixed $value): ?Carbon
    {
        $value = $this->stringOrNull($value);

        if ($value === null) {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay();
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

    private function isLegacyUserInsert(string $statement): bool
    {
        return (bool) preg_match('/INSERT\s+INTO\s+`?legacy_tbl_user`?/i', $statement);
    }
}

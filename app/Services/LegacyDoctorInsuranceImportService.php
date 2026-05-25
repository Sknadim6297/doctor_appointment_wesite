<?php

namespace App\Services;

use App\Models\ComboPlan;
use App\Models\Enrollment;
use App\Models\HighRiskPlan;
use App\Models\NormalPlan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LegacyDoctorInsuranceImportService
{
    public function __construct(
        private readonly LegacySpecializationImportService $specializationImport,
    ) {
    }

    public function loadSqlFile(string $path, bool $truncateStaging = true): int
    {
        if (!is_file($path)) {
            throw new \InvalidArgumentException("SQL file not found: {$path}");
        }

        if (!Schema::hasTable('legacy_tbl_doctor_insurance')) {
            throw new \RuntimeException('Run migrations first (legacy_tbl_doctor_insurance table missing).');
        }

        $sql = file_get_contents($path);

        if ($sql === false || trim($sql) === '') {
            throw new \InvalidArgumentException('SQL file is empty or unreadable.');
        }

        $sql = str_replace('`tbl_doctor_insurance`', '`legacy_tbl_doctor_insurance`', $sql);
        $sql = str_replace("'0000-00-00'", 'NULL', $sql);
        $sql = preg_replace("/'0000-00-00 00:00:00'/", 'NULL', $sql) ?? $sql;
        $sql = preg_replace('/CREATE\s+TABLE\s+`?(?:legacy_tbl_doctor_insurance|tbl_doctor_insurance)`?.*?;\s*/is', '', $sql) ?? $sql;
        $sql = preg_replace('/ALTER\s+TABLE\s+`?(?:legacy_tbl_doctor_insurance|tbl_doctor_insurance)`?.*?;\s*/is', '', $sql) ?? $sql;

        if ($truncateStaging) {
            DB::table('legacy_tbl_doctor_insurance')->truncate();
        }

        $inserted = 0;

        foreach ($this->splitSqlStatements($sql) as $statement) {
            $statement = $this->stripSqlComments($statement);

            if ($statement === '' || !preg_match('/INSERT\s+INTO\s+`?legacy_tbl_doctor_insurance`?/i', $statement)) {
                continue;
            }

            try {
                DB::unprepared($statement);
                $inserted += substr_count($statement, '),(') + 1;
            } catch (\Throwable $exception) {
                throw new \RuntimeException(
                    'Failed executing legacy_tbl_doctor_insurance INSERT: ' . $exception->getMessage(),
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
    public function syncToEnrollments(bool $dryRun = false): array
    {
        if (!Schema::hasTable('legacy_tbl_doctor_insurance')) {
            throw new \RuntimeException('legacy_tbl_doctor_insurance table missing.');
        }

        $stats = ['updated' => 0, 'skipped' => 0, 'errors' => []];
        $detailsByUser = $this->loadDoctorDetailsByUserId();

        DB::table('legacy_tbl_doctor_insurance')->orderBy('id')->chunk(200, function ($rows) use ($dryRun, $detailsByUser, &$stats) {
            foreach ($rows as $row) {
                $legacyUserId = (int) $row->doctor_id;

                try {
                    $enrollment = Enrollment::query()
                        ->where('legacy_user_id', $legacyUserId)
                        ->first();

                    if (!$enrollment) {
                        $stats['skipped']++;

                        continue;
                    }

                    $payload = $this->mapLegacyRow($row, $detailsByUser[$legacyUserId] ?? null);

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
                    $stats['errors'][$legacyUserId] = $exception->getMessage();
                }
            }
        });

        return $stats;
    }

    public function stagingRowCount(): int
    {
        return Schema::hasTable('legacy_tbl_doctor_insurance')
            ? (int) DB::table('legacy_tbl_doctor_insurance')->count()
            : 0;
    }

    /**
     * @return array<string, mixed>
     */
    private function mapLegacyRow(object $row, ?object $doctorDetails): array
    {
        $planType = $this->normalizePlanType($row->plan_id ?? null);
        $coverageLakh = $this->parseCoverageLakh($row->legal_service ?? null, $row->insurance_coverage ?? null);
        $specializationId = null;

        if ($doctorDetails !== null) {
            $specializationId = $this->specializationImport->resolveLegacySpecializationId($doctorDetails->specilality_id ?? null);
        }

        $coveragePlanId = $this->resolveCoveragePlanId($planType, $coverageLakh, $specializationId);

        $payload = array_filter([
            'plan' => $planType,
            'plan_name' => $this->resolvePlanName($planType, $coverageLakh),
            'coverage' => $coverageLakh,
            'coverage_id' => $coveragePlanId,
            'payment_mode' => $this->stringOrNull($row->payment_mode),
            'policy_no' => $this->stringOrNull($row->policy_no ?? null),
            'service_amount' => $this->parseAmount($row->insurance_amount ?? null),
            'payment_amount' => $this->parseAmount($row->medeforum_amount ?? null),
            'policy_date' => $this->parseDate($row->policy_date ?? null) ?? $this->parseDate($row->enrollment_date ?? null),
            'payment_cash_date' => $this->parseDate($row->policy_date ?? null) ?? $this->parseDate($row->enrollment_date ?? null),
            'renewal_date' => $this->parseDate($row->renewal_date ?? null),
            'last_renewal_date' => $this->parseDate($row->enrollment_date ?? null),
        ], static fn (mixed $value): bool => $value !== null);

        if ($specializationId !== null) {
            $payload['specialization_id'] = $specializationId;
        }

        $insuranceAmount = $payload['service_amount'] ?? null;
        $medeforumAmount = $payload['payment_amount'] ?? null;

        if ($insuranceAmount !== null || $medeforumAmount !== null) {
            $payload['total_amount'] = round((float) ($insuranceAmount ?? 0) + (float) ($medeforumAmount ?? 0), 2);
        }

        return $payload;
    }

    /**
     * @return array<int, object>
     */
    private function loadDoctorDetailsByUserId(): array
    {
        if (!Schema::hasTable('legacy_tbl_doctor_details')) {
            return [];
        }

        $map = [];

        foreach (DB::table('legacy_tbl_doctor_details')->get() as $row) {
            $userId = (int) ($row->user_id ?? 0);

            if ($userId > 0) {
                $map[$userId] = $row;
            }
        }

        return $map;
    }

    private function normalizePlanType(mixed $value): ?int
    {
        $plan = is_numeric($value) ? (int) $value : null;

        return in_array($plan, [1, 2, 3], true) ? $plan : null;
    }

    private function parseCoverageLakh(mixed $legalService, mixed $insuranceCoverage): ?float
    {
        foreach ([$legalService, $insuranceCoverage] as $value) {
            $amount = $this->parseAmount($value);

            if ($amount !== null && $amount > 0) {
                return $amount;
            }
        }

        return null;
    }

    private function resolveCoveragePlanId(?int $planType, ?float $coverageLakh, ?int $specializationId): ?int
    {
        if ($planType === null || $coverageLakh === null || $coverageLakh <= 0) {
            return null;
        }

        if ($planType === 3 && $specializationId !== null && Schema::hasTable('combo_plan_specialization')) {
            $planId = DB::table('combo_plan_specialization')
                ->join('combo_plans', 'combo_plans.id', '=', 'combo_plan_specialization.combo_plan_id')
                ->where('combo_plan_specialization.specialization_id', $specializationId)
                ->where('combo_plans.coverage_lakh', $coverageLakh)
                ->value('combo_plans.id');

            if ($planId !== null) {
                return (int) $planId;
            }
        }

        $model = match ($planType) {
            1 => NormalPlan::class,
            2 => HighRiskPlan::class,
            3 => ComboPlan::class,
            default => null,
        };

        if ($model === null) {
            return null;
        }

        $planId = $model::query()
            ->where('coverage_lakh', $coverageLakh)
            ->orderBy('id')
            ->value('id');

        return $planId !== null ? (int) $planId : null;
    }

    private function resolvePlanName(?int $planType, ?float $coverageLakh): ?string
    {
        $label = match ($planType) {
            1 => 'Normal',
            2 => 'High Risk',
            3 => 'Combo',
            default => null,
        };

        if ($label === null) {
            return null;
        }

        if ($coverageLakh !== null && $coverageLakh > 0) {
            return $label . ' - ' . rtrim(rtrim(number_format($coverageLakh, 2, '.', ''), '0'), '.') . ' Lakh';
        }

        return $label;
    }

    private function parseAmount(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        if ($string === '' || $string === '0') {
            return null;
        }

        $normalized = str_replace(',', '', $string);

        if (preg_match('/([\d]+(?:\.\d+)?)/', $normalized, $matches) !== 1) {
            return null;
        }

        $amount = (float) $matches[1];

        return $amount > 0 ? round($amount, 2) : null;
    }

    private function parseDate(mixed $value): ?string
    {
        $string = trim((string) ($value ?? ''));

        if ($string === '' || str_starts_with($string, '0000')) {
            return null;
        }

        return $string;
    }

    private function stringOrNull(mixed $value): ?string
    {
        $string = trim((string) ($value ?? ''));

        return $string !== '' ? $string : null;
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

    private function stripSqlComments(string $sql): string
    {
        $sql = preg_replace('/^--.*$/m', '', $sql) ?? $sql;

        return trim($sql);
    }
}

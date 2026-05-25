<?php

namespace App\Services;

use App\Models\ComboPlan;
use App\Models\Enrollment;
use App\Models\NormalPlan;
use App\Models\PolicyReceipt;
use App\Models\RenewalHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LegacyRenewalHistoryImportService
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

        if (!Schema::hasTable('legacy_tbl_renew_history')) {
            throw new \RuntimeException('Run migrations first (legacy_tbl_renew_history table missing).');
        }

        $sql = file_get_contents($path);

        if ($sql === false || trim($sql) === '') {
            throw new \InvalidArgumentException('SQL file is empty or unreadable.');
        }

        $sql = str_replace('`tbl_renew_history`', '`legacy_tbl_renew_history`', $sql);
        $sql = str_replace("'0000-00-00'", 'NULL', $sql);
        $sql = preg_replace("/'0000-00-00 00:00:00'/", 'NULL', $sql) ?? $sql;
        $sql = preg_replace('/CREATE\s+TABLE\s+`?(?:legacy_tbl_renew_history|tbl_renew_history)`?.*?;\s*/is', '', $sql) ?? $sql;
        $sql = preg_replace('/ALTER\s+TABLE\s+`?(?:legacy_tbl_renew_history|tbl_renew_history)`?.*?;\s*/is', '', $sql) ?? $sql;

        if ($truncateStaging) {
            DB::table('legacy_tbl_renew_history')->truncate();
        }

        $inserted = 0;

        foreach ($this->splitSqlStatements($sql) as $statement) {
            $statement = $this->stripSqlComments($statement);

            if ($statement === '' || !preg_match('/INSERT\s+INTO\s+`?legacy_tbl_renew_history`?/i', $statement)) {
                continue;
            }

            try {
                DB::unprepared($statement);
                $inserted += substr_count($statement, '),(') + 1;
            } catch (\Throwable $exception) {
                throw new \RuntimeException(
                    'Failed executing legacy_tbl_renew_history INSERT: ' . $exception->getMessage(),
                    (int) $exception->getCode(),
                    $exception
                );
            }
        }

        return $inserted;
    }

    /**
     * @return array{histories:int,enrollments_updated:int,policies_updated:int,skipped:int,errors:array<int,string>}
     */
    public function syncToApp(bool $dryRun = false, bool $truncateHistories = false): array
    {
        if (!Schema::hasTable('legacy_tbl_renew_history')) {
            throw new \RuntimeException('legacy_tbl_renew_history table missing.');
        }

        $stats = [
            'histories' => 0,
            'enrollments_updated' => 0,
            'policies_updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        if ($truncateHistories && !$dryRun) {
            RenewalHistory::query()->delete();
        }

        $detailsByUser = $this->loadDoctorDetailsByUserId();

        DB::table('legacy_tbl_renew_history')->orderBy('id')->chunk(500, function ($rows) use ($dryRun, $detailsByUser, &$stats) {
            foreach ($rows as $row) {
                $legacyDoctorId = (int) ($row->renew_doctor_id ?? 0);

                if ($legacyDoctorId <= 0) {
                    $stats['skipped']++;

                    continue;
                }

                try {
                    $enrollment = Enrollment::query()
                        ->where('legacy_user_id', $legacyDoctorId)
                        ->first();

                    if (!$enrollment) {
                        $stats['skipped']++;

                        continue;
                    }

                    $historyPayload = $this->mapHistoryRow($row);
                    $historyPayload['enrollment_id'] = $enrollment->id;

                    if ($dryRun) {
                        $stats['histories']++;

                        continue;
                    }

                    RenewalHistory::query()->create($historyPayload);
                    $stats['histories']++;
                } catch (\Throwable $exception) {
                    $stats['errors'][$legacyDoctorId] = $exception->getMessage();
                }
            }
        });

        if ($dryRun) {
            return $stats;
        }

        $latestByDoctor = $this->buildLatestRenewalByDoctor();

        foreach ($latestByDoctor as $legacyDoctorId => $row) {
            try {
                $enrollment = Enrollment::query()
                    ->where('legacy_user_id', $legacyDoctorId)
                    ->first();

                if (!$enrollment) {
                    $stats['skipped']++;

                    continue;
                }

                $renewedDate = $this->parseDate($row->renewed_date ?? null);
                $planType = $this->normalizePlanType($row->renew_plan_id ?? null);
                $coverageLakh = $this->parseCoverageLakh($row->renew_legal_service ?? null, $row->renew_insurance_coverage ?? null);
                $specializationId = isset($detailsByUser[$legacyDoctorId])
                    ? $this->specializationImport->resolveLegacySpecializationId($detailsByUser[$legacyDoctorId]->specilality_id ?? null)
                    : null;
                $coveragePlanId = $this->resolveCoveragePlanId($planType, $coverageLakh, $specializationId);

                $insuranceAmount = $this->parseAmount($row->renew_insurance_amount ?? null);
                $medeforumAmount = $this->parseAmount($row->renew_medeforum_amount ?? null);
                $policyNo = $this->stringOrNull($row->renew_policy_no ?? null);

                $enrollmentPayload = array_filter([
                    'last_renewal_date' => $renewedDate,
                    'renewal_date' => $this->nextRenewalDate($renewedDate, $row->renew_payment_mode ?? null),
                    'plan' => $planType,
                    'plan_name' => $this->resolvePlanName($planType, $coverageLakh),
                    'coverage' => $coverageLakh,
                    'coverage_id' => $coveragePlanId,
                    'payment_mode' => $this->stringOrNull($row->renew_payment_mode ?? null),
                    'service_amount' => $insuranceAmount,
                    'payment_amount' => $medeforumAmount,
                ], static fn (mixed $value): bool => $value !== null);

                if ($specializationId !== null) {
                    $enrollmentPayload['specialization_id'] = $specializationId;
                }

                if ($insuranceAmount !== null || $medeforumAmount !== null) {
                    $enrollmentPayload['total_amount'] = round((float) ($insuranceAmount ?? 0) + (float) ($medeforumAmount ?? 0), 2);
                }

                if ($enrollmentPayload !== []) {
                    $enrollment->fill($enrollmentPayload);
                    $enrollment->save();
                    $stats['enrollments_updated']++;
                }

                if ($renewedDate !== null && Schema::hasTable('policy_receipts')) {
                    $policy = PolicyReceipt::query()
                        ->where('enrollment_id', $enrollment->id)
                        ->orderByDesc('id')
                        ->first();

                    if ($policy) {
                        $policyUpdates = array_filter([
                            'last_renewed_date' => $renewedDate,
                            'policy_no' => $policyNo ?? $policy->policy_no,
                        ], static fn (mixed $value): bool => $value !== null);

                        if ($policyUpdates !== []) {
                            $policy->fill($policyUpdates);
                            $policy->save();
                            $stats['policies_updated']++;
                        }
                    } elseif ($policyNo !== null) {
                        PolicyReceipt::query()->create([
                            'enrollment_id' => $enrollment->id,
                            'doctor_name' => $enrollment->doctor_name,
                            'policy_no' => $policyNo,
                            'last_renewed_date' => $renewedDate,
                            'workflow_status' => PolicyReceipt::STATUS_COMPLETED,
                        ]);
                        $stats['policies_updated']++;
                    }
                }
            } catch (\Throwable $exception) {
                $stats['errors'][$legacyDoctorId] = $exception->getMessage();
            }
        }

        return $stats;
    }

    public function stagingRowCount(): int
    {
        return Schema::hasTable('legacy_tbl_renew_history')
            ? (int) DB::table('legacy_tbl_renew_history')->count()
            : 0;
    }

    /**
     * @return array<int, object>
     */
    private function buildLatestRenewalByDoctor(): array
    {
        $latest = [];

        DB::table('legacy_tbl_renew_history')
            ->orderBy('renew_doctor_id')
            ->orderByDesc('renewed_date')
            ->orderByDesc('id')
            ->chunk(1000, function ($rows) use (&$latest) {
                foreach ($rows as $row) {
                    $doctorId = (int) ($row->renew_doctor_id ?? 0);

                    if ($doctorId <= 0) {
                        continue;
                    }

                    if (! isset($latest[$doctorId])) {
                        $latest[$doctorId] = $row;

                        continue;
                    }

                    $currentDate = $this->parseDate($latest[$doctorId]->renewed_date ?? null);
                    $candidateDate = $this->parseDate($row->renewed_date ?? null);

                    if ($this->isRenewalNewer($candidateDate, $currentDate, (int) ($row->id ?? 0), (int) ($latest[$doctorId]->id ?? 0))) {
                        $latest[$doctorId] = $row;
                    }
                }
            });

        return $latest;
    }

    /**
     * @return array<string, mixed>
     */
    private function mapHistoryRow(object $row): array
    {
        $planType = $this->normalizePlanType($row->renew_plan_id ?? null);
        $coverageLakh = $this->parseCoverageLakh($row->renew_legal_service ?? null, $row->renew_insurance_coverage ?? null);

        return [
            'legacy_renewal_id' => is_numeric($row->id ?? null) ? (int) $row->id : null,
            'legacy_doctor_id' => (int) ($row->renew_doctor_id ?? 0),
            'renewed_date' => $this->parseDate($row->renewed_date ?? null),
            'renew_month' => $this->stringOrNull($row->renew_month ?? null),
            'renew_day' => $this->stringOrNull($row->renew_day ?? null),
            'renew_year' => $this->stringOrNull($row->renew_year ?? null),
            'medeforum_amount' => $this->parseAmount($row->renew_medeforum_amount ?? null),
            'insurance_amount' => $this->parseAmount($row->renew_insurance_amount ?? null),
            'coverage_lakh' => $coverageLakh,
            'plan_type' => $planType,
            'payment_mode' => $this->stringOrNull($row->renew_payment_mode ?? null),
            'policy_no' => $this->stringOrNull($row->renew_policy_no ?? null),
        ];
    }

    private function nextRenewalDate(?string $renewedDate, mixed $paymentMode): ?string
    {
        if ($renewedDate === null) {
            return null;
        }

        try {
            $date = Carbon::parse($renewedDate);
        } catch (\Throwable) {
            return null;
        }

        $years = $this->paymentModeYears($paymentMode);

        return $date->copy()->addYears($years)->format('Y-m-d');
    }

    private function paymentModeYears(mixed $paymentMode): int
    {
        $mode = strtolower(trim((string) ($paymentMode ?? '')));

        if (preg_match('/(\d+)\s*year/', $mode, $matches) === 1) {
            return max(1, (int) $matches[1]);
        }

        $wordYears = [
            'one' => 1,
            'two' => 2,
            'three' => 3,
            'four' => 4,
            'five' => 5,
        ];

        foreach ($wordYears as $word => $years) {
            if (str_contains($mode, $word)) {
                return $years;
            }
        }

        return 1;
    }

    private function isRenewalNewer(?string $candidateDate, ?string $currentDate, int $candidateId, int $currentId): bool
    {
        if ($candidateDate === null) {
            return false;
        }

        if ($currentDate === null) {
            return true;
        }

        if ($candidateDate > $currentDate) {
            return true;
        }

        if ($candidateDate < $currentDate) {
            return false;
        }

        return $candidateId >= $currentId;
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

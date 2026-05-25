<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\PolicyReceipt;
use App\Models\RenewalHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LegacyRenewalPaymentImportService
{
    public function __construct(
        protected Enrollment $enrollment,
        protected RenewalHistory $renewalHistory,
        protected PolicyReceipt $policyReceipt,
    ) {}

    /**
     * @return array{loaded: int, synced: int, skipped: int, errors: array<int, string>, policies_updated: int}
     */
    public function loadSqlFile(string $path, bool $truncate = true): array
    {
        if (! Schema::hasTable('legacy_tbl_renewal_payment')) {
            return ['loaded' => 0, 'synced' => 0, 'skipped' => 0, 'errors' => ['Table legacy_tbl_renewal_payment does not exist. Run migrations.'], 'policies_updated' => 0];
        }

        if (! is_readable($path)) {
            return ['loaded' => 0, 'synced' => 0, 'skipped' => 0, 'errors' => ["File not readable: {$path}"], 'policies_updated' => 0];
        }

        if ($truncate) {
            DB::table('legacy_tbl_renewal_payment')->truncate();
        }

        $sql = file_get_contents($path);
        if ($sql === false) {
            return ['loaded' => 0, 'synced' => 0, 'skipped' => 0, 'errors' => ['Could not read file.'], 'policies_updated' => 0];
        }

        $sql = $this->stripSqlComments($sql);
        $statements = $this->splitInsertStatements($sql);
        $loaded = 0;

        foreach ($statements as $statement) {
            if (! preg_match('/^INSERT\s+INTO\s+`legacy_tbl_renewal_payment`/i', $statement)) {
                continue;
            }

            try {
                DB::unprepared($statement);
                $loaded++;
            } catch (\Throwable $e) {
                // skip bad rows
            }
        }

        return [
            'loaded' => $loaded,
            'synced' => 0,
            'skipped' => 0,
            'errors' => [],
            'policies_updated' => 0,
        ];
    }

    /**
     * @return array{loaded: int, synced: int, skipped: int, errors: array<int, string>, policies_updated: int}
     */
    public function sync(bool $dryRun = false, bool $truncateBeforeLoad = true, bool $keepHistories = false): array
    {
        $stats = [
            'loaded' => 0,
            'synced' => 0,
            'skipped' => 0,
            'errors' => [],
            'policies_updated' => 0,
        ];

        if (! Schema::hasTable('legacy_tbl_renewal_payment')) {
            return $stats;
        }

        if ($truncateBeforeLoad) {
            DB::table('legacy_tbl_renewal_payment')->truncate();
        }

        $latestByDoctor = $this->buildLatestPaymentByDoctor();

        foreach ($latestByDoctor as $legacyDoctorId => $row) {
            $enrollment = $this->enrollment->newQuery()
                ->where('legacy_user_id', $legacyDoctorId)
                ->first();

            if (! $enrollment) {
                $stats['skipped']++;
                $stats['errors'][$legacyDoctorId] = 'No enrollment for legacy_user_id';

                continue;
            }

            $renewedDate = $this->parseDate($row->payment_date ?? null);
            if ($renewedDate === null) {
                $stats['skipped']++;
                $stats['errors'][$legacyDoctorId] = 'Invalid payment_date';

                continue;
            }

            $planType = $this->normalizePlanType($row->plan_id ?? null, $row->payment_mode ?? null);
            $amount = $this->parseAmount($row->payment_amount ?? null);
            $policyNo = $this->stringOrNull($row->policy_no ?? null);
            $planCode = $this->mapPlanIdToEnrollmentPlan($row->plan_id ?? null);

            if ($dryRun) {
                $stats['synced']++;

                continue;
            }

            $history = $this->renewalHistory->newQuery()
                ->where('enrollment_id', $enrollment->id)
                ->orderByDesc('renewed_date')
                ->orderByDesc('id')
                ->first();

            $nextRenewal = $this->nextRenewalDate($renewedDate, $row->payment_mode ?? null);

            if ($history) {
                $history->update([
                    'renewed_date' => $renewedDate,
                    'medeforum_amount' => $amount,
                    'plan_type' => $planType,
                    'policy_no' => $policyNo,
                    'next_renewal_date' => $nextRenewal,
                ]);
            } else {
                $this->renewalHistory->create([
                    'enrollment_id' => $enrollment->id,
                    'renewed_date' => $renewedDate,
                    'medeforum_amount' => $amount,
                    'plan_type' => $planType,
                    'policy_no' => $policyNo,
                    'next_renewal_date' => $nextRenewal,
                ]);
            }

            $enrollmentUpdates = [
                'last_renewal_date' => $renewedDate->format('Y-m-d'),
                'renewal_date' => $renewedDate->format('Y-m-d'),
            ];
            if ($planCode !== null) {
                $enrollmentUpdates['plan'] = $planCode;
            }
            if ($amount !== null) {
                $enrollmentUpdates['payment_amount'] = $amount;
            }
            if ($policyNo !== null) {
                $enrollmentUpdates['policy_no'] = $policyNo;
            }
            $enrollment->update($enrollmentUpdates);

            $receipt = $this->policyReceipt->newQuery()
                ->where('enrollment_id', $enrollment->id)
                ->first();

            $receiptUpdates = [
                'last_renewed_date' => $renewedDate->format('Y-m-d'),
                'workflow_status' => PolicyReceipt::STATUS_COMPLETED,
            ];
            if ($policyNo !== null) {
                $receiptUpdates['policy_no'] = $policyNo;
            }
            $receiptUpdates['renewal_plan'] = $this->mapPlanTypeToRenewalPlanEnum($planType);
            if (Schema::hasColumn('policy_receipts', 'plan_amount')) {
                $receiptUpdates['plan_amount'] = $amount;
            }

            if ($receipt) {
                $receipt->update($receiptUpdates);
                $stats['policies_updated']++;
            } else {
                $receiptUpdates['enrollment_id'] = $enrollment->id;
                $receiptUpdates['doctor_name'] = $enrollment->doctor_name;
                if ($policyNo !== null) {
                    $receiptUpdates['policy_no'] = $policyNo;
                }
                $this->policyReceipt->create($receiptUpdates);
                $stats['policies_updated']++;
            }

            $stats['synced']++;
        }

        return $stats;
    }

    /**
     * Map legacy plan_id to enrollments.plan (1=Normal, 2=HighRisk, 3=Combo).
     */
    private function mapPlanIdToEnrollmentPlan(?int $planId): ?int
    {
        if ($planId === 1) {
            return 1;
        }
        if ($planId === 2) {
            return 2;
        }
        if ($planId === 3) {
            return 3;
        }

        return 1;
    }

    private function mapPlanTypeToRenewalPlanEnum(string $planType): string
    {
        return match ($planType) {
            'combo' => 'combo',
            'high_risk' => 'high_risk',
            'yearly' => 'yearly',
            default => 'insurance',
        };
    }

    /**
     * @return array<int, object>
     */
    protected function buildLatestPaymentByDoctor(): array
    {
        $rows = DB::table('legacy_tbl_renewal_payment')
            ->orderBy('doctor_id')
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->get();

        $latest = [];
        foreach ($rows as $row) {
            $doctorId = (int) $row->doctor_id;
            if (! isset($latest[$doctorId])) {
                $latest[$doctorId] = $row;
            }
        }

        return $latest;
    }

    protected function stripSqlComments(string $sql): string
    {
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

        return trim($sql);
    }

    /**
     * @return array<int, string>
     */
    protected function splitInsertStatements(string $sql): array
    {
        $parts = preg_split('/;\s*(?=INSERT\s+INTO\s+`legacy_tbl_renewal_payment`)/i', $sql);
        $statements = [];

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part !== '' && stripos($part, 'INSERT') === 0) {
                $statements[] = $part;
            }
        }

        return $statements;
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

    protected function normalizePlanType(mixed $planId, mixed $paymentMode): string
    {
        if (is_numeric($planId)) {
            $map = [
                1 => RenewalHistory::PLAN_TYPE_INSURANCE,
                2 => RenewalHistory::PLAN_TYPE_COMBO,
                3 => RenewalHistory::PLAN_TYPE_YEARLY,
            ];

            $id = (int) $planId;

            if (isset($map[$id])) {
                return $map[$id];
            }
        }

        $mode = strtolower(trim((string) ($paymentMode ?? '')));

        if (str_contains($mode, 'yearly') || str_contains($mode, 'one year')) {
            return RenewalHistory::PLAN_TYPE_YEARLY;
        }
        if (str_contains($mode, 'two year') || str_contains($mode, '2 year')) {
            return RenewalHistory::PLAN_TYPE_TWO_YEAR;
        }
        if (str_contains($mode, 'three year') || str_contains($mode, '3 year')) {
            return RenewalHistory::PLAN_TYPE_THREE_YEAR;
        }
        if (str_contains($mode, 'four year') || str_contains($mode, '4 year')) {
            return RenewalHistory::PLAN_TYPE_FOUR_YEAR;
        }
        if (str_contains($mode, 'five year') || str_contains($mode, '5 year')) {
            return RenewalHistory::PLAN_TYPE_FIVE_YEAR;
        }

        return RenewalHistory::PLAN_TYPE_INSURANCE;
    }

    protected function planLabelFromType(string $planType): string
    {
        return match ($planType) {
            RenewalHistory::PLAN_TYPE_YEARLY => 'yearly',
            RenewalHistory::PLAN_TYPE_TWO_YEAR => 'two_year',
            RenewalHistory::PLAN_TYPE_THREE_YEAR => 'three_year',
            RenewalHistory::PLAN_TYPE_FOUR_YEAR => 'four_year',
            RenewalHistory::PLAN_TYPE_FIVE_YEAR => 'five_year',
            default => $planType,
        };
    }

    protected function parseDate(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $value = trim($value);

        if ($value === '0000-00-00' || str_starts_with($value, '0000-00-00 00:00:00')) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    protected function parseAmount(?string $value): float
    {
        if ($value === null || trim($value) === '') {
            return 0.0;
        }

        if (preg_match('/[\d,]+\.?\d*/', $value, $matches) === 1) {
            return (float) $matches[0];
        }

        return 0.0;
    }

    protected function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\PolicyReceipt;
use App\Models\RenewalHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LegacyDoctorMoneyReceiptImportService
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
        if (! Schema::hasTable('legacy_tbl_doctor_money_reciept')) {
            return ['loaded' => 0, 'synced' => 0, 'skipped' => 0, 'errors' => ['Table legacy_tbl_doctor_money_reciept does not exist. Run migrations.'], 'policies_updated' => 0];
        }

        if (! is_readable($path)) {
            return ['loaded' => 0, 'synced' => 0, 'skipped' => 0, 'errors' => ["File not readable: {$path}"], 'policies_updated' => 0];
        }

        if ($truncate) {
            DB::table('legacy_tbl_doctor_money_reciept')->truncate();
        }

        $sql = file_get_contents($path);
        if ($sql === false) {
            return ['loaded' => 0, 'synced' => 0, 'skipped' => 0, 'errors' => ['Could not read file.'], 'policies_updated' => 0];
        }

        $sql = str_replace('`tbl_doctor_money_reciept`', '`legacy_tbl_doctor_money_reciept`', $sql);
        $sql = str_replace("'0000-00-00'", 'NULL', $sql);
        $sql = preg_replace("/'(\d{4}-\d{2}-\d{2})', '', '/m", "'$1', NULL, ", $sql) ?? $sql;
        $sql = $this->stripSqlComments($sql);
        $statements = $this->splitSqlStatements($sql);
        $loaded = 0;

        foreach ($statements as $statement) {
            if (! preg_match('/INSERT\s+INTO\s+`?legacy_tbl_doctor_money_reciept`?/i', $statement)) {
                continue;
            }

            try {
                DB::unprepared($statement . ';');
                $loaded += max(1, substr_count($statement, '),(') + 1);
            } catch (\Throwable $e) {
                throw new \RuntimeException(
                    'Failed executing legacy_tbl_doctor_money_reciept INSERT: ' . $e->getMessage(),
                    (int) $e->getCode(),
                    $e
                );
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

        if (! Schema::hasTable('legacy_tbl_doctor_money_reciept')) {
            return $stats;
        }

        if ($truncateBeforeLoad) {
            DB::table('legacy_tbl_doctor_money_reciept')->truncate();
        }

        $rowsByDoctor = $this->groupStagingRowsByDoctor();

        foreach ($rowsByDoctor as $legacyDoctorId => $rows) {
            $enrollment = $this->enrollment->newQuery()
                ->where('legacy_user_id', $legacyDoctorId)
                ->first();

            if (! $enrollment) {
                $stats['skipped']++;
                $stats['errors'][$legacyDoctorId] = 'No enrollment for legacy_user_id';

                continue;
            }

            $enrollmentReceipt = $this->pickEnrollmentReceipt($rows);

            if ($enrollmentReceipt === null) {
                $stats['skipped']++;
                $stats['errors'][$legacyDoctorId] = 'No money receipt row with number/date';

                continue;
            }

            if ($dryRun) {
                $stats['synced']++;

                continue;
            }

            $enrollmentUpdates = [];
            $this->applyMoneyReceiptToEnrollment($enrollmentReceipt, $enrollmentUpdates);

            if ($enrollmentUpdates === []) {
                $stats['skipped']++;

                continue;
            }

            $enrollment->update($enrollmentUpdates);
            $stats['synced']++;
        }

        return $stats;
    }

    public function stagingRowCount(): int
    {
        return Schema::hasTable('legacy_tbl_doctor_money_reciept')
            ? (int) DB::table('legacy_tbl_doctor_money_reciept')->count()
            : 0;
    }

    /**
     * @return array<int, \Illuminate\Support\Collection<int, object>>
     */
    private function groupStagingRowsByDoctor(): array
    {
        $grouped = [];

        foreach (DB::table('legacy_tbl_doctor_money_reciept')->orderBy('doctor_id')->orderBy('id')->cursor() as $row) {
            $doctorId = (int) ($row->doctor_id ?? 0);
            if ($doctorId <= 0) {
                continue;
            }

            $grouped[$doctorId][] = $row;
        }

        return $grouped;
    }

    /**
     * @param  array<int, object>  $rows
     */
    private function pickEnrollmentReceipt(array $rows): ?object
    {
        $enrollmentRows = array_values(array_filter(
            $rows,
            fn (object $row): bool => strtolower((string) ($row->payment_for ?? '')) === 'enrollment'
        ));

        if ($enrollmentRows !== []) {
            usort($enrollmentRows, fn (object $a, object $b): int => $this->compareReceiptDates($b, $a));

            return $enrollmentRows[0];
        }

        return $this->pickLatestReceipt($rows) ?? $this->pickFirstReceiptWithNumber($rows);
    }

    /**
     * @param  array<int, object>  $rows
     */
    private function pickFirstReceiptWithNumber(array $rows): ?object
    {
        foreach ($rows as $row) {
            if (filled($row->money_reciept_no ?? null)) {
                return $row;
            }
        }

        return null;
    }

    /**
     * @param  array<int, object>  $rows
     */
    private function pickLatestReceipt(array $rows): ?object
    {
        $datedRows = array_values(array_filter(
            $rows,
            fn (object $row): bool => $this->parseDate($row->money_reciept_date ?? null) !== null
        ));

        if ($datedRows === []) {
            return null;
        }

        usort($datedRows, fn (object $a, object $b): int => $this->compareReceiptDates($b, $a));

        return $datedRows[0];
    }

    private function compareReceiptDates(object $a, object $b): int
    {
        $dateA = $this->parseDate($a->money_reciept_date ?? null);
        $dateB = $this->parseDate($b->money_reciept_date ?? null);

        if ($dateA === null && $dateB === null) {
            return ((int) ($a->id ?? 0)) <=> ((int) ($b->id ?? 0));
        }

        if ($dateA === null) {
            return 1;
        }

        if ($dateB === null) {
            return -1;
        }

        return $dateA->timestamp <=> $dateB->timestamp;
    }

    /**
     * @param  array<string, mixed>  $enrollmentUpdates
     */
    private function applyMoneyReceiptToEnrollment(object $row, array &$enrollmentUpdates): void
    {
        if ($row->money_reciept_no !== null && $row->money_reciept_no !== '') {
            $receiptNo = (string) (int) $row->money_reciept_no;
            $enrollmentUpdates['doctor_money_reciept_no'] = (int) $row->money_reciept_no;
            $enrollmentUpdates['money_rc_no'] = $receiptNo;
        }

        if (filled($row->money_reciept_year ?? null)) {
            $enrollmentUpdates['doctor_money_reciept_year'] = (string) $row->money_reciept_year;
        }

        $receiptDate = $this->parseDate($row->money_reciept_date ?? null);
        if ($receiptDate !== null) {
            $enrollmentUpdates['payment_cash_date'] = $receiptDate->format('Y-m-d');
        }
    }

    private function extractPolicyNo(?string $renewalBond, ?string $enrollmentBond): ?string
    {
        foreach ([$enrollmentBond, $renewalBond] as $bond) {
            if ($bond === null || trim($bond) === '') {
                continue;
            }

            $bond = trim($bond);
            if (preg_match('/^medeforum-IND-19000786-M-(\d+)/i', $bond, $matches)) {
                return 'medeforum-IND-19000786-M-' . $matches[1];
            }

            return $bond;
        }

        return null;
    }

    private function parseAmount(?string $raw): ?float
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }
        $clean = preg_replace('/[^\d.]/', '', trim($raw));
        if ($clean === '' || ! is_numeric($clean)) {
            return null;
        }

        return (float) $clean;
    }

    private function parseDate(?string $raw): ?Carbon
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }
        try {
            return Carbon::parse($raw);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function mapPlanIdToEnrollmentPlan(?int $planId): ?int
    {
        return match ($planId) {
            2 => 2,
            3 => 3,
            default => 1,
        };
    }

    private function mapPlanIdToRenewalPlanType(mixed $planId): int
    {
        $plan = is_numeric($planId) ? (int) $planId : 1;

        return in_array($plan, [1, 2, 3], true) ? $plan : 1;
    }

    private function nextRenewalDate(Carbon $receiptDate, ?string $paymentMode): ?Carbon
    {
        if ($paymentMode === 'yearly') {
            return $receiptDate->copy()->addYear();
        }
        if ($paymentMode === 'half_yearly') {
            return $receiptDate->copy()->addMonths(6);
        }

        return $receiptDate->copy()->addYear();
    }

    private function stripSqlComments(string $sql): string
    {
        $sql = preg_replace('/^--.*$/m', '', $sql) ?? $sql;
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql) ?? $sql;

        return trim($sql);
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
}
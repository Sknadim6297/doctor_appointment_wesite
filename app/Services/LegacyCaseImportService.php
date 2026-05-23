<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\LegalCase;
use App\Models\LegalCaseCategory;
use App\Models\LegalCaseDocument;
use App\Models\LegalCasePayment;
use App\Models\LegalCaseProceeding;
use App\Models\LegalCourtLink;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use stdClass;

class LegacyCaseImportService
{
    private const STAGING_TABLES = [
        'category' => ['legacy_tbl_case_category', 'tbl_case_category', 'case_cat_id'],
        'doctor_case' => ['legacy_tbl_doctor_case', 'tbl_doctor_case', 'case_id'],
        'details' => ['legacy_tbl_case_details', 'tbl_case_details', 'case_details_id'],
        'document' => ['legacy_tbl_case_document', 'tbl_case_document', 'id'],
        'payment' => ['legacy_tbl_case_payment', 'tbl_case_payment', 'case_payment_id'],
        'link' => ['legacy_tbl_case_link', 'tbl_case_link', 'id'],
        'case' => ['legacy_tbl_case', 'tbl_case', 'case_id'],
    ];

    private const SYNTHETIC_PROCEEDING_ID_OFFSET = 900_000_000;

    private const SYNTHETIC_PAYMENT_ID_OFFSET = 800_000_000;

    public function loadSqlFile(string $type, string $path, bool $truncateStaging = true): int
    {
        if (!isset(self::STAGING_TABLES[$type])) {
            throw new \InvalidArgumentException("Unknown legacy case staging type: {$type}");
        }

        [$stagingTable, $sourceTable] = self::STAGING_TABLES[$type];

        if (!is_file($path)) {
            throw new \InvalidArgumentException("SQL file not found: {$path}");
        }

        if (!Schema::hasTable($stagingTable)) {
            throw new \RuntimeException("Run migrations first ({$stagingTable} table missing).");
        }

        $sql = file_get_contents($path);

        if ($sql === false || trim($sql) === '') {
            throw new \InvalidArgumentException('SQL file is empty or unreadable.');
        }

        $sql = str_replace("`{$sourceTable}`", "`{$stagingTable}`", $sql);
        $sql = preg_replace('/CREATE\s+TABLE\s+`?(?:' . preg_quote($stagingTable, '/') . '|' . preg_quote($sourceTable, '/') . ')`?.*?;\s*/is', '', $sql) ?? $sql;
        $sql = preg_replace('/ALTER\s+TABLE\s+`?(?:' . preg_quote($stagingTable, '/') . '|' . preg_quote($sourceTable, '/') . ')`?.*?;\s*/is', '', $sql) ?? $sql;
        $sql = preg_replace("/'0000-00-00'/", 'NULL', $sql) ?? $sql;

        if ($truncateStaging) {
            DB::table($stagingTable)->truncate();
        }

        $inserted = 0;

        foreach ($this->splitSqlStatements($sql) as $statement) {
            $statement = $this->stripSqlComments($statement);

            if ($statement === '' || !preg_match('/\bINSERT\s+INTO\s+`?' . preg_quote($stagingTable, '/') . '`?/i', $statement)) {
                continue;
            }

            try {
                DB::unprepared($statement);
                $inserted += $this->countInsertRows($statement);
            } catch (\Throwable $exception) {
                throw new \RuntimeException(
                    "Failed executing {$stagingTable} INSERT: " . $exception->getMessage(),
                    (int) $exception->getCode(),
                    $exception
                );
            }
        }

        return $inserted;
    }

    /**
     * @return array{created:int,updated:int,skipped:int,errors:array<int,string>,proceedings:int,documents:int,payments:int}
     */
    public function syncFromStaging(bool $dryRun = false): array
    {
        $stats = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
            'proceedings' => 0,
            'documents' => 0,
            'payments' => 0,
        ];

        $this->syncCategories($dryRun);
        $this->syncCourtLinks($dryRun);
        $this->ensureDefaultCaseCategory();
        $this->ensureLegacyCaseIdsInStaging();

        $defaultCourtLink = LegalCourtLink::query()->orderBy('id')->value('url')
            ?? 'http://cms.nic.in/ncdrcusersWeb/courtroommodule.do?method=loadCaseHistory';

        $categoryName = LegalCaseCategory::query()->find(1)?->name ?? 'Medical';

        $legacyCaseIds = $this->collectLegacyCaseIds();

        foreach ($legacyCaseIds as $legacyCaseId) {
            try {
                $doctorCase = $this->doctorCaseForId($legacyCaseId);

                if ($doctorCase !== null && $this->shouldSkipLegacyDoctorCase($doctorCase)) {
                    $stats['skipped']++;

                    continue;
                }

                $result = $this->syncOneCase($legacyCaseId, $categoryName, $defaultCourtLink, $dryRun, $doctorCase);
                $stats[$result['action']]++;
            } catch (\Throwable $exception) {
                $stats['errors'][$legacyCaseId] = $exception->getMessage();
            }
        }

        if (!$dryRun) {
            $stats['proceedings'] = $this->syncProceedings();
            $stats['proceedings'] += $this->syncProceedingsFromDoctorCases();
            $stats['documents'] = $this->syncDocuments();
            $stats['payments'] = $this->syncPayments();
            $stats['payments'] += $this->syncPaymentsFromDoctorCases();
        }

        return $stats;
    }

    public function stagingRowCount(string $type): int
    {
        if (!isset(self::STAGING_TABLES[$type])) {
            return 0;
        }

        [$stagingTable] = self::STAGING_TABLES[$type];

        return Schema::hasTable($stagingTable) ? (int) DB::table($stagingTable)->count() : 0;
    }

    private function syncCategories(bool $dryRun): void
    {
        if (!Schema::hasTable('legacy_tbl_case_category')) {
            return;
        }

        foreach (DB::table('legacy_tbl_case_category')->orderBy('case_cat_id')->get() as $row) {
            if ($dryRun) {
                continue;
            }

            LegalCaseCategory::query()->updateOrCreate(
                ['id' => (int) $row->case_cat_id],
                ['name' => (string) $row->case_cat_name]
            );
        }
    }

    private function syncCourtLinks(bool $dryRun): void
    {
        if (!Schema::hasTable('legacy_tbl_case_link')) {
            return;
        }

        foreach (DB::table('legacy_tbl_case_link')->orderBy('id')->get() as $row) {
            if ($dryRun) {
                continue;
            }

            LegalCourtLink::query()->updateOrCreate(
                ['id' => (int) $row->id],
                [
                    'name' => (string) $row->case_link_name,
                    'url' => (string) $row->case_link,
                ]
            );
        }
    }

    private function ensureLegacyCaseIdsInStaging(): void
    {
        if (!Schema::hasTable('legacy_tbl_case')) {
            return;
        }

        $ids = $this->collectLegacyCaseIds();

        foreach ($ids as $legacyCaseId) {
            DB::table('legacy_tbl_case')->updateOrInsert(
                ['case_id' => $legacyCaseId],
                ['case_id' => $legacyCaseId]
            );
        }
    }

    private function ensureDefaultCaseCategory(): void
    {
        if (!Schema::hasTable('legal_case_categories')) {
            return;
        }

        LegalCaseCategory::query()->firstOrCreate(
            ['id' => 1],
            ['name' => 'Medical']
        );
    }

    /**
     * @return array<int>
     */
    private function collectLegacyCaseIds(): array
    {
        $ids = [];

        foreach (['legacy_tbl_doctor_case', 'legacy_tbl_case', 'legacy_tbl_case_details', 'legacy_tbl_case_document', 'legacy_tbl_case_payment'] as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $column = $table === 'legacy_tbl_case' ? 'case_id' : 'case_id';

            foreach (DB::table($table)->distinct()->pluck($column) as $id) {
                $ids[(int) $id] = (int) $id;
            }
        }

        ksort($ids);

        return array_values($ids);
    }

    /**
     * @return array{action:string}
     */
    private function syncOneCase(int $legacyCaseId, string $categoryName, string $defaultCourtLink, bool $dryRun, ?stdClass $doctorCase = null): array
    {
        $doctorCase ??= $this->doctorCaseForId($legacyCaseId);

        if ($doctorCase !== null) {
            return $this->syncOneCaseFromDoctorCase($legacyCaseId, $doctorCase, $categoryName, $defaultCourtLink, $dryRun);
        }

        $stagingCase = Schema::hasTable('legacy_tbl_case')
            ? DB::table('legacy_tbl_case')->where('case_id', $legacyCaseId)->first()
            : null;

        $details = $this->detailsForCase($legacyCaseId);
        $documents = $this->documentsForCase($legacyCaseId);
        $payments = $this->paymentsForCase($legacyCaseId);

        $texts = array_merge(
            $details->pluck('case_details')->all(),
            $documents->pluck('document_title')->all()
        );

        $caseNumber = $stagingCase?->case_number
            ?? $this->extractCaseNumber($texts);

        $doctorName = $stagingCase?->doctor_name
            ?? $this->extractDoctorName($documents, $texts);

        $stage = $stagingCase?->stage
            ?? $this->inferStage($details);

        $latestDetail = $details->sortByDesc('date')->first();
        $latestPayment = $payments->first();

        $enrollmentId = $stagingCase?->enrollment_id
            ? (int) $stagingCase->enrollment_id
            : $this->resolveEnrollmentId($doctorName, $texts);

        if (($doctorName === null || $doctorName === '') && $enrollmentId !== null) {
            $doctorName = Enrollment::query()->whereKey($enrollmentId)->value('doctor_name');
        }

        if ($doctorName === null || $doctorName === '') {
            $doctorName = 'Legacy Case #' . $legacyCaseId;
        }

        $createdBy = $this->resolveUserId($latestDetail?->created_by);

        $payload = [
            'legacy_case_id' => $legacyCaseId,
            'legal_case_category_id' => 1,
            'enrollment_id' => $enrollmentId,
            'doctor_name' => $doctorName,
            'case_number' => $caseNumber,
            'case_cat' => $stagingCase?->case_cat_name ?? $categoryName,
            'stage' => $stage,
            'case_details' => $latestDetail ? $this->truncate((string) $latestDetail->case_details, 65000) : null,
            'next_date' => $this->parseDate($latestDetail?->date),
            'case_link' => $defaultCourtLink,
            'direct_payment' => $latestPayment !== null,
            'payment_cheque_no' => $latestPayment?->cheque_no,
            'direct_payment_bank' => $latestPayment?->bank_name,
            'direct_payment_amount' => $this->parseAmount($latestPayment?->amount),
            'check_date' => $this->parsePaymentDate($latestPayment?->payment_date),
            'created_by' => $createdBy,
        ];

        if ($dryRun) {
            return ['action' => LegalCase::query()->where('legacy_case_id', $legacyCaseId)->exists() ? 'updated' : 'created'];
        }

        $existing = LegalCase::query()->where('legacy_case_id', $legacyCaseId)->first();

        if ($existing) {
            $existing->update($payload);

            return ['action' => 'updated'];
        }

        LegalCase::query()->create($payload);

        return ['action' => 'created'];
    }

    /**
     * @return array{action:string}
     */
    private function syncOneCaseFromDoctorCase(int $legacyCaseId, stdClass $row, string $categoryName, string $defaultCourtLink, bool $dryRun): array
    {
        $enrollment = $this->resolveEnrollmentByLegacyUserId($row->doctor_id);
        $doctorName = $enrollment?->doctor_name;

        if ($doctorName === null || trim($doctorName) === '') {
            $doctorName = 'Legacy Case #' . $legacyCaseId;
        }

        $categoryId = (int) ($row->cat_id ?? 0);
        $legalCategoryId = $categoryId > 0 && LegalCaseCategory::query()->whereKey($categoryId)->exists()
            ? $categoryId
            : 1;

        $hasPayment = $this->stringOrNull($row->cheque_no) !== null
            || $this->stringOrNull($row->bank) !== null
            || $this->stringOrNull($row->payment_amount) !== null
            || $this->stringOrNull($row->money_receipt) !== null;

        $caseLink = $this->stringOrNull($row->case_link) ?? $defaultCourtLink;

        $payload = [
            'legacy_case_id' => $legacyCaseId,
            'legal_case_category_id' => $legalCategoryId,
            'enrollment_id' => $enrollment?->id,
            'doctor_name' => $doctorName,
            'doctor_phone' => $this->stringOrNull($row->doctor_mobile),
            'doctor_mail' => $this->normalizeEmail($row->doctor_email),
            'case_number' => $this->stringOrNull($row->case_number),
            'court_year' => $this->parseCourtYear($row->court_year),
            'court' => $this->normalizeCourt($row->court),
            'court_address' => $this->stringOrNull($row->court_address),
            'case_cat' => LegalCaseCategory::query()->find($legalCategoryId)?->name ?? $categoryName,
            'stage' => $this->normalizeStage($row->stage),
            'case_details' => $this->truncate((string) ($row->case_details ?? ''), 65000),
            'advocat_mobile' => $this->stringOrNull($row->advocat_mobile),
            'advocat_mail' => $this->normalizeEmail($row->advocat_mail),
            'appear_date' => $this->parseDate($row->appear_date),
            'next_date' => $this->parseDate($row->next_date),
            'filling_date' => $this->parseDate($row->filling_date),
            'complainant_name' => $this->stringOrNull($row->complainant_name),
            'mail_link' => $this->stringOrNull($row->mail_link),
            'direct_payment' => $hasPayment,
            'money_reciept_no' => $this->stringOrNull($row->money_receipt),
            'payment_cheque_no' => $this->stringOrNull($row->cheque_no),
            'direct_payment_bank' => $this->stringOrNull($row->bank),
            'bank_branch' => $this->stringOrNull($row->bank_branch),
            'direct_payment_amount' => $this->parseAmount($row->payment_amount),
            'check_date' => $this->parseDate($row->check_date),
            'case_link' => $caseLink,
            'created_by' => $this->resolveUserId($row->created_by ?? $row->edited_by),
        ];

        if ($dryRun) {
            return ['action' => LegalCase::query()->where('legacy_case_id', $legacyCaseId)->exists() ? 'updated' : 'created'];
        }

        $existing = LegalCase::query()->where('legacy_case_id', $legacyCaseId)->first();

        if ($existing) {
            $existing->update($payload);

            return ['action' => 'updated'];
        }

        LegalCase::query()->create($payload);

        return ['action' => 'created'];
    }

    private function doctorCaseForId(int $legacyCaseId): ?stdClass
    {
        if (!Schema::hasTable('legacy_tbl_doctor_case')) {
            return null;
        }

        $row = DB::table('legacy_tbl_doctor_case')->where('case_id', $legacyCaseId)->first();

        return $row instanceof stdClass ? $row : null;
    }

    private function shouldSkipLegacyDoctorCase(stdClass $row): bool
    {
        $doctorId = (int) ($row->doctor_id ?? 0);
        $catId = (int) ($row->cat_id ?? 0);
        $caseNumber = strtolower(trim((string) ($row->case_number ?? '')));

        return $doctorId === 0
            && $catId === 0
            && in_array($caseNumber, ['', '0', 'cc/000/0000'], true);
    }

    private function resolveEnrollmentByLegacyUserId(mixed $legacyUserId): ?Enrollment
    {
        $legacyUserId = is_numeric($legacyUserId) ? (int) $legacyUserId : 0;

        if ($legacyUserId <= 0) {
            return null;
        }

        return Enrollment::query()->where('legacy_user_id', $legacyUserId)->first();
    }

    private function syncProceedingsFromDoctorCases(): int
    {
        if (!Schema::hasTable('legacy_tbl_doctor_case')) {
            return 0;
        }

        $count = 0;
        $caseMap = LegalCase::query()->whereNotNull('legacy_case_id')->pluck('id', 'legacy_case_id');

        foreach (DB::table('legacy_tbl_doctor_case')->orderBy('case_id')->cursor() as $row) {
            $legacyCaseId = (int) $row->case_id;
            $legalCaseId = $caseMap[$legacyCaseId] ?? null;

            if ($legalCaseId === null || $this->shouldSkipLegacyDoctorCase($row)) {
                continue;
            }

            if ($this->detailsForCase($legacyCaseId)->isNotEmpty()) {
                continue;
            }

            $body = trim((string) ($row->case_details ?? ''));

            if ($body === '') {
                continue;
            }

            $proceedingId = self::SYNTHETIC_PROCEEDING_ID_OFFSET + $legacyCaseId;

            LegalCaseProceeding::query()->updateOrCreate(
                ['id' => $proceedingId],
                [
                    'legal_case_id' => $legalCaseId,
                    'legacy_case_id' => $legacyCaseId,
                    'body' => $body,
                    'proceed_date' => $this->parseDate($row->edited_on) ?? $this->parseDate($row->next_date),
                    'legacy_created_by' => (int) ($row->created_by ?? 0),
                    'legacy_edited_by' => (int) ($row->edited_by ?? 0),
                ]
            );
            $count++;
        }

        return $count;
    }

    private function syncPaymentsFromDoctorCases(): int
    {
        if (!Schema::hasTable('legacy_tbl_doctor_case')) {
            return 0;
        }

        $count = 0;
        $caseMap = LegalCase::query()->whereNotNull('legacy_case_id')->pluck('id', 'legacy_case_id');

        foreach (DB::table('legacy_tbl_doctor_case')->orderBy('case_id')->cursor() as $row) {
            $legacyCaseId = (int) $row->case_id;
            $legalCaseId = $caseMap[$legacyCaseId] ?? null;

            if ($legalCaseId === null || $this->shouldSkipLegacyDoctorCase($row)) {
                continue;
            }

            if ($this->paymentsForCase($legacyCaseId)->isNotEmpty()) {
                continue;
            }

            $hasPayment = $this->stringOrNull($row->cheque_no) !== null
                || $this->stringOrNull($row->bank) !== null
                || $this->stringOrNull($row->payment_amount) !== null;

            if (!$hasPayment) {
                continue;
            }

            $paymentId = self::SYNTHETIC_PAYMENT_ID_OFFSET + $legacyCaseId;

            LegalCasePayment::query()->updateOrCreate(
                ['id' => $paymentId],
                [
                    'legal_case_id' => $legalCaseId,
                    'legacy_case_id' => $legacyCaseId,
                    'cheque_no' => $this->stringOrNull($row->cheque_no),
                    'bank_name' => $this->stringOrNull($row->bank),
                    'amount' => $this->stringOrNull($row->payment_amount),
                    'payment_date' => $this->parsePaymentDate($row->check_date),
                    'acknowledge_reciept' => $this->stringOrNull($row->money_receipt),
                ]
            );
            $count++;
        }

        return $count;
    }

    private function syncProceedings(): int
    {
        $count = 0;

        if (!Schema::hasTable('legacy_tbl_case_details')) {
            return 0;
        }

        $caseMap = LegalCase::query()->whereNotNull('legacy_case_id')->pluck('id', 'legacy_case_id');

        foreach (DB::table('legacy_tbl_case_details')->orderBy('case_details_id')->cursor() as $row) {
            $legalCaseId = $caseMap[(int) $row->case_id] ?? null;

            if ($legalCaseId === null) {
                continue;
            }

            LegalCaseProceeding::query()->updateOrCreate(
                ['id' => (int) $row->case_details_id],
                [
                    'legal_case_id' => $legalCaseId,
                    'legacy_case_id' => (int) $row->case_id,
                    'body' => (string) $row->case_details,
                    'proceed_date' => $this->parseDate($row->date),
                    'legacy_created_by' => (int) $row->created_by,
                    'legacy_edited_by' => (int) $row->edited_by,
                ]
            );
            $count++;
        }

        return $count;
    }

    private function syncDocuments(): int
    {
        $count = 0;

        if (!Schema::hasTable('legacy_tbl_case_document')) {
            return 0;
        }

        $caseMap = LegalCase::query()->whereNotNull('legacy_case_id')->pluck('id', 'legacy_case_id');

        foreach (DB::table('legacy_tbl_case_document')->orderBy('id')->cursor() as $row) {
            $legalCaseId = $caseMap[(int) $row->case_id] ?? null;

            if ($legalCaseId === null) {
                continue;
            }

            LegalCaseDocument::query()->updateOrCreate(
                ['id' => (int) $row->id],
                [
                    'legal_case_id' => $legalCaseId,
                    'legacy_case_id' => (int) $row->case_id,
                    'document_title' => (string) $row->document_title,
                    'file_slug' => (string) $row->document_file,
                ]
            );
            $count++;
        }

        return $count;
    }

    private function syncPayments(): int
    {
        $count = 0;

        if (!Schema::hasTable('legacy_tbl_case_payment')) {
            return 0;
        }

        $caseMap = LegalCase::query()->whereNotNull('legacy_case_id')->pluck('id', 'legacy_case_id');

        foreach (DB::table('legacy_tbl_case_payment')->orderBy('case_payment_id')->cursor() as $row) {
            $legalCaseId = $caseMap[(int) $row->case_id] ?? null;

            if ($legalCaseId === null) {
                continue;
            }

            LegalCasePayment::query()->updateOrCreate(
                ['id' => (int) $row->case_payment_id],
                [
                    'legal_case_id' => $legalCaseId,
                    'legacy_case_id' => (int) $row->case_id,
                    'cheque_no' => $row->cheque_no,
                    'bank_name' => $row->bank_name,
                    'amount' => $row->amount,
                    'payment_date' => $row->payment_date,
                    'acknowledge_reciept' => $row->acknowledge_reciept,
                ]
            );
            $count++;
        }

        return $count;
    }

    private function detailsForCase(int $legacyCaseId)
    {
        if (!Schema::hasTable('legacy_tbl_case_details')) {
            return collect();
        }

        return DB::table('legacy_tbl_case_details')->where('case_id', $legacyCaseId)->get();
    }

    private function documentsForCase(int $legacyCaseId)
    {
        if (!Schema::hasTable('legacy_tbl_case_document')) {
            return collect();
        }

        return DB::table('legacy_tbl_case_document')->where('case_id', $legacyCaseId)->get();
    }

    private function paymentsForCase(int $legacyCaseId)
    {
        if (!Schema::hasTable('legacy_tbl_case_payment')) {
            return collect();
        }

        return DB::table('legacy_tbl_case_payment')->where('case_id', $legacyCaseId)->orderBy('case_payment_id')->get();
    }

    /**
     * @param  array<int, string>  $texts
     */
    private function extractCaseNumber(array $texts): ?string
    {
        foreach ($texts as $text) {
            if (preg_match('/\b(CC\s*\/\s*\d+[A-Z]?\s*\/\s*\d{4})\b/i', $text, $match)) {
                return preg_replace('/\s+/', '', strtoupper($match[1])) ?? $match[1];
            }

            if (preg_match('/\bCase\s+No\.?\s*:?\s*(CC\s*\/\s*\d+[A-Z]?\s*\/\s*\d{4})\b/i', $text, $match)) {
                return preg_replace('/\s+/', '', strtoupper($match[1])) ?? $match[1];
            }
        }

        return null;
    }

    /**
     * @param  array<int, string>  $texts
     */
    private function extractDoctorName($documents, array $texts): ?string
    {
        foreach ($documents as $document) {
            $title = (string) $document->document_title;

            if (preg_match('/\bDR\.?\s*([A-Z][A-Z\s.]+?)(?:\s+CASE|\s+VAKALAT|\s+INSURANCE|\s+RTGS|\s+LEGAL|\s+PAYMENT|$)/i', $title, $match)) {
                return $this->normalizeDoctorName($match[1]);
            }
        }

        foreach ($texts as $text) {
            if (preg_match('/\bDr\.?\s+([A-Z][a-z]+(?:\s+[A-Z][a-z]+)+)/', $text, $match)) {
                return trim($match[1]);
            }
        }

        return null;
    }

    private function normalizeDoctorName(string $raw): string
    {
        $name = preg_replace('/\s+/', ' ', trim($raw)) ?? $raw;

        return ucwords(strtolower($name));
    }

    private function inferStage($details): ?string
    {
        $latest = $details->sortByDesc('date')->first();

        if ($latest === null) {
            return null;
        }

        $body = strtoupper((string) $latest->case_details);

        if (str_contains($body, 'DISMISSED') || str_contains($body, 'DISPOSED') || str_contains($body, 'JUDGEMENT') || str_contains($body, 'JUDGMENT')) {
            return 'Disposed';
        }

        if (str_contains($body, 'ONGOING')) {
            return 'Ongoing';
        }

        return 'Active';
    }

    /**
     * @param  array<int, string>  $texts
     */
    private function resolveEnrollmentId(?string $doctorName, array $texts): ?int
    {
        if ($doctorName !== null && $doctorName !== '') {
            $normalized = strtolower($doctorName);
            $match = Enrollment::query()
                ->whereNotNull('legacy_user_id')
                ->whereRaw('LOWER(doctor_name) = ?', [$normalized])
                ->first();

            if ($match) {
                return (int) $match->id;
            }

            $match = Enrollment::query()
                ->whereNotNull('legacy_user_id')
                ->where('doctor_name', 'like', '%' . $doctorName . '%')
                ->orderBy('id')
                ->first();

            if ($match) {
                return (int) $match->id;
            }
        }

        foreach ($texts as $text) {
            if (!preg_match('/\bDr\.?\s+([A-Za-z][A-Za-z\s.]+)/', $text, $match)) {
                continue;
            }

            $hint = trim($match[1]);

            $found = Enrollment::query()
                ->whereNotNull('legacy_user_id')
                ->where('doctor_name', 'like', '%' . $hint . '%')
                ->orderBy('id')
                ->first();

            if ($found) {
                return (int) $found->id;
            }
        }

        return null;
    }

    private function resolveUserId(mixed $legacyUserId): ?int
    {
        $legacyUserId = (int) $legacyUserId;

        if ($legacyUserId <= 0) {
            return null;
        }

        return User::query()->where('legacy_user_id', $legacyUserId)->value('id');
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
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseCourtYear(mixed $value): ?int
    {
        $value = $this->stringOrNull($value);

        if ($value === null || $value === '0') {
            return null;
        }

        if (preg_match('/\b(19|20)\d{2}\b/', $value, $match)) {
            return (int) $match[0];
        }

        return is_numeric($value) ? (int) $value : null;
    }

    private function normalizeCourt(mixed $value): ?string
    {
        $value = strtolower($this->stringOrNull($value) ?? '');

        if ($value === '') {
            return null;
        }

        return match ($value) {
            'district', 'state', 'national' => ucfirst($value),
            default => $this->stringOrNull($value),
        };
    }

    private function normalizeStage(mixed $value): ?string
    {
        $stage = $this->stringOrNull($value);

        if ($stage === null) {
            return null;
        }

        $upper = strtoupper($stage);

        if (str_contains($upper, 'CLOSED') || str_contains($upper, 'DISMISS') || str_contains($upper, 'DISPOSED') || str_contains($upper, 'JUDGEMENT') || str_contains($upper, 'JUDGMENT')) {
            return 'Disposed';
        }

        if (str_contains($upper, 'ONGOING')) {
            return 'Ongoing';
        }

        return $this->truncate($stage, 65000);
    }

    private function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '' || $value === '0' || strtolower($value) === 'n.a' || strtolower($value) === 'n.a.') {
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

    private function parsePaymentDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = trim((string) $value);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseAmount(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (!preg_match_all('/\d+(?:\.\d+)?/', $value, $matches)) {
            return null;
        }

        $best = null;

        foreach ($matches[0] as $raw) {
            $amount = (float) str_replace(',', '', $raw);

            if ($amount < 100 || $amount > 99_999_999.99) {
                continue;
            }

            if (preg_match('/\b(19|20)\d{2}\b/', $value) && (int) $raw >= 1900 && (int) $raw <= 2100) {
                continue;
            }

            $best = $best === null ? $amount : max($best, $amount);
        }

        return $best;
    }

    private function truncate(string $value, int $max): string
    {
        return strlen($value) <= $max ? $value : substr($value, 0, $max);
    }

    /**
     * @return array<int, string>
     */
    private function splitSqlStatements(string $sql): array
    {
        $parts = preg_split('/;\s*[\r\n]+/', $sql) ?: [];

        return array_map('trim', $parts);
    }

    private function stripSqlComments(string $statement): string
    {
        $statement = preg_replace('/--.*$/m', '', $statement) ?? $statement;
        $statement = preg_replace('/\/\*.*?\*\//s', '', $statement) ?? $statement;

        return trim($statement);
    }

    private function countInsertRows(string $statement): int
    {
        return substr_count($statement, '),(') + (preg_match('/\bVALUES\s*\(/i', $statement) ? 1 : 0);
    }
}

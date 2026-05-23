<?php

namespace App\Console\Commands;

use App\Services\LegacyCaseImportService;
use Illuminate\Console\Command;

class ImportLegacyCasesCommand extends Command
{
    protected $signature = 'legacy:import-cases
                            {--doctor-case=storage/app/legacy_tbl_doctor_case.sql : Main tbl_doctor_case SQL dump}
                            {--category=storage/app/legacy_tbl_case_category.sql : Category SQL dump}
                            {--details=storage/app/legacy_tbl_case_details.sql : Case details / proceedings SQL dump}
                            {--documents=storage/app/legacy_tbl_case_documents.sql : Case documents SQL dump}
                            {--payments=storage/app/legacy_tbl_case_payment.sql : Case payments SQL dump}
                            {--links=storage/app/legacy_tbl_case_link.sql : Court links SQL dump}
                            {--case= : Optional main tbl_case SQL dump}
                            {--sync-only : Sync from staging without loading SQL}
                            {--dry-run : Preview without writing}
                            {--no-truncate : Append to staging instead of truncating}';

    protected $description = 'Import legacy legal case data (categories, proceedings, documents, payments) into legal_cases.';

    public function handle(LegacyCaseImportService $importService): int
    {
        $syncOnly = (bool) $this->option('sync-only');

        if (!$syncOnly) {
            $loads = [
                'doctor_case' => (string) $this->option('doctor-case'),
                'category' => (string) $this->option('category'),
                'details' => (string) $this->option('details'),
                'document' => (string) $this->option('documents'),
                'payment' => (string) $this->option('payments'),
                'link' => (string) $this->option('links'),
            ];

            $caseFile = $this->option('case');

            if ($caseFile !== null && $caseFile !== '') {
                $loads['case'] = (string) $caseFile;
            }

            foreach ($loads as $type => $path) {
                if (!is_file($path)) {
                    $this->warn("Skipping {$type}: file not found ({$path})");

                    continue;
                }

                $this->info("Loading {$type} from {$path}...");

                try {
                    $loaded = $importService->loadSqlFile($type, $path, !$this->option('no-truncate'));
                    $this->line("  ~{$loaded} row value group(s) executed.");
                } catch (\Throwable $exception) {
                    $this->error($exception->getMessage());

                    return self::FAILURE;
                }
            }
        }

        $doctorCaseCount = $importService->stagingRowCount('doctor_case');
        $detailsCount = $importService->stagingRowCount('details');

        if ($doctorCaseCount === 0 && $detailsCount === 0 && $importService->stagingRowCount('document') === 0) {
            $this->warn('No doctor cases, details, or documents in staging. Place SQL dumps in storage/app/ and re-run.');

            return self::FAILURE;
        }

        $this->info("Staging doctor cases: {$doctorCaseCount}, details: {$detailsCount}, documents: {$importService->stagingRowCount('document')}, payments: {$importService->stagingRowCount('payment')}");

        try {
            $stats = $importService->syncFromStaging((bool) $this->option('dry-run'));
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->line('Dry run — no changes written.');
        }

        $this->table(
            ['Metric', 'Count'],
            [
                ['Cases created', $stats['created']],
                ['Cases updated', $stats['updated']],
                ['Cases skipped', $stats['skipped']],
                ['Proceedings synced', $stats['proceedings']],
                ['Documents synced', $stats['documents']],
                ['Payments synced', $stats['payments']],
                ['Errors', count($stats['errors'])],
            ]
        );

        if ($stats['errors'] !== []) {
            foreach ($stats['errors'] as $legacyId => $message) {
                $this->warn("Case {$legacyId}: {$message}");
            }

            return self::FAILURE;
        }

        $this->info('Case import finished. Open Admin → Cases to review.');

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Services\LegacyDoctorMoneyReceiptImportService;
use Illuminate\Console\Command;

class ImportLegacyDoctorMoneyReceiptsCommand extends Command
{
    protected $signature = 'legacy:import-doctor-money-receipt
                            {--file=storage/app/legacy_tbl_doctor_money_reciept.sql : Path to legacy tbl_doctor_money_reciept SQL dump}
                            {--sync-only : Sync from staging without loading SQL}
                            {--dry-run : Preview counts without writing}
                            {--no-truncate : Append to staging instead of truncating before load}
                            {--keep-histories : Reserved for future use}';

    protected $description = 'Import legacy doctor money receipts into enrollments, renewal histories, and policy receipts';

    public function handle(LegacyDoctorMoneyReceiptImportService $service): int
    {
        $syncOnly = (bool) $this->option('sync-only');
        $file = (string) $this->option('file');
        $dryRun = (bool) $this->option('dry-run');

        if (! $syncOnly) {
            if (! is_file($file)) {
                $this->error("SQL file not found: {$file}");
                $this->line('Place the phpMyAdmin dump at storage/app/legacy_tbl_doctor_money_reciept.sql or pass --file=');

                return self::FAILURE;
            }

            $this->info("Loading SQL from {$file}...");

            $loadResult = $service->loadSqlFile($file, ! $this->option('no-truncate'));

            if (! empty($loadResult['errors'])) {
                foreach ($loadResult['errors'] as $error) {
                    $this->error($error);
                }

                return self::FAILURE;
            }

            $this->info('INSERT statements executed: ' . $loadResult['loaded']);
        }

        $stagingCount = $service->stagingRowCount();

        if ($stagingCount === 0) {
            $this->warn('No rows in legacy_tbl_doctor_money_reciept staging table.');

            return self::FAILURE;
        }

        $this->info("Staging rows: {$stagingCount}");

        $result = $service->sync($dryRun, false);

        $this->line('Synced enrollments: ' . $result['synced']);
        $this->line('Skipped: ' . $result['skipped']);
        $this->line('Policy receipts updated/created: ' . $result['policies_updated']);

        if (! empty($result['errors'])) {
            foreach (array_slice($result['errors'], 0, 15, true) as $doctorId => $message) {
                $this->warn("  doctor_id {$doctorId}: {$message}");
            }
            if (count($result['errors']) > 15) {
                $this->warn('  ... and ' . (count($result['errors']) - 15) . ' more');
            }
        }

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Services\LegacyRenewalPaymentImportService;
use Illuminate\Console\Command;

class ImportLegacyRenewalPaymentCommand extends Command
{
    protected $signature = 'legacy:import-renewal-payment
                            {--file= : SQL file path (default: storage/app/legacy_tbl_renewal_payment.sql)}
                            {--dry-run : Parse only, no writes}
                            {--keep-histories : Keep all payment rows per doctor (default: latest only)}
                            {--truncate : Truncate staging table before load}';

    protected $description = 'Load tbl_renewal_payment SQL and sync to renewal_histories, enrollments, policy_receipts';

    public function handle(LegacyRenewalPaymentImportService $service): int
    {
        $file = $this->option('file') ?: storage_path('app/legacy_tbl_renewal_payment.sql');
        if (! is_file($file)) {
            $this->error('File not found: ' . $file);

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $load = $service->loadSqlFile($file, (bool) $this->option('truncate'));
            $this->info('Dry run: would load ' . $load['loaded'] . ' rows (not written).');

            return self::SUCCESS;
        }

        $load = $service->loadSqlFile($file, (bool) $this->option('truncate'));
        $this->info('Loaded ' . $load['loaded'] . ' rows into legacy_tbl_renewal_payment.');

        $result = $service->sync(
            keepHistories: (bool) $this->option('keep-histories'),
            dryRun: false,
            truncateBeforeLoad: false
        );

        $this->info('Synced: ' . $result['synced'] . ', skipped doctors: ' . $result['skipped']);
        if (! empty($result['errors'])) {
            foreach (array_slice($result['errors'], 0, 20) as $err) {
                $this->warn('  - ' . $err);
            }
            if (count($result['errors']) > 20) {
                $this->warn('  ... and ' . (count($result['errors']) - 20) . ' more.');
            }
        }

        return self::SUCCESS;
    }
}
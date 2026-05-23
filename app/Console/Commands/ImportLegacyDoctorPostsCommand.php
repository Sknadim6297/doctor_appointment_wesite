<?php

namespace App\Console\Commands;

use App\Services\LegacyDoctorPostImportService;
use Illuminate\Console\Command;

class ImportLegacyDoctorPostsCommand extends Command
{
    protected $signature = 'legacy:import-doctor-posts
                            {--file= : Path to legacy tbl_doctor_post SQL dump}
                            {--sync-only : Sync from staging without loading SQL}
                            {--dry-run : Preview counts without writing doctor_posts}
                            {--no-truncate : Append to staging instead of truncating before load}';

    protected $description = 'Load legacy tbl_doctor_post and sync consignment/post records into doctor_posts linked by doctor_id → enrollments.legacy_user_id.';

    public function handle(LegacyDoctorPostImportService $importService): int
    {
        $file = $this->option('file');
        $syncOnly = (bool) $this->option('sync-only');
        $dryRun = (bool) $this->option('dry-run');

        if (!$syncOnly) {
            if (!$file) {
                $default = storage_path('app/legacy_tbl_doctor_post.sql');

                if (is_file($default)) {
                    $file = $default;
                    $this->comment("Using default file: {$file}");
                } else {
                    $this->error('Provide --file=path/to/dump.sql or use --sync-only after loading staging.');

                    return self::FAILURE;
                }
            }

            $this->info("Loading SQL from {$file}...");

            try {
                $loaded = $importService->loadSqlFile(
                    $file,
                    !$this->option('no-truncate')
                );
            } catch (\Throwable $exception) {
                $this->error($exception->getMessage());

                return self::FAILURE;
            }

            $this->info("Executed INSERT statements (~{$loaded} row value groups).");
        }

        $stagingCount = $importService->stagingRowCount();

        if ($stagingCount === 0) {
            $this->warn('No rows in legacy_tbl_doctor_post staging table.');

            return self::FAILURE;
        }

        $this->info("Staging doctor posts: {$stagingCount}");

        if ($dryRun) {
            $this->comment('Dry run — no doctor_posts writes.');
        }

        $stats = $importService->syncFromStaging($dryRun);

        $this->table(
            ['Metric', 'Count'],
            [
                ['Created', $stats['created']],
                ['Updated', $stats['updated']],
                ['Linked to enrollment', $stats['linked']],
                ['Skipped (invalid post_id / empty row)', $stats['skipped']],
                ['Errors', count($stats['errors'])],
            ]
        );

        if ($stats['errors'] !== []) {
            $this->warn('Import errors (sample):');

            foreach (array_slice($stats['errors'], 0, 10, true) as $legacyPostId => $message) {
                $this->line("  post_id {$legacyPostId}: {$message}");
            }

            return self::FAILURE;
        }

        $this->info($dryRun ? 'Dry run complete.' : 'Legacy doctor posts import complete.');

        return self::SUCCESS;
    }
}

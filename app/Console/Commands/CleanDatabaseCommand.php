<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CleanDatabaseCommand extends Command
{
    protected $signature = 'db:clean
                            {--force : Skip confirmation prompt}
                            {--dry-run : Show what would be removed without changing data}';

    protected $description = 'Remove all non-master data while preserving master tables and admin/super-admin accounts.';

    public function handle(): int
    {
        $preservedTables = [
            'migrations',
            'admin_roles',
            'admin_role_user',
            'admin_privileges',
            'specializations',
            'normal_plans',
            'high_risk_plans',
            'combo_plans',
            'insurance_plans',
        ];

        $preservedRoles = ['super_admin', 'admin'];
        $preservedUserIds = Schema::hasTable('users')
            ? User::query()->whereIn('role', $preservedRoles)->pluck('id')->all()
            : [];

        $tables = $this->listTables();
        $tablesToTruncate = array_values(array_filter(
            $tables,
            fn (string $table): bool => !in_array($table, $preservedTables, true) && $table !== 'users'
        ));

        $this->info('Preserving tables: ' . implode(', ', $preservedTables));
        $this->info('Preserving ' . count($preservedUserIds) . ' admin/super-admin user(s).');
        $this->info('Tables to clean: ' . count($tablesToTruncate) . ' table(s).');

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->line('Dry run only. No changes made.');

            return self::SUCCESS;
        }

        if (!$this->option('force') && !$this->confirm('This will delete all non-master data and all non-admin users. Continue?')) {
            $this->info('Cancelled.');

            return self::SUCCESS;
        }

        Schema::disableForeignKeyConstraints();

        try {
            $this->cleanUsers($preservedUserIds, $preservedRoles);

            foreach ($tablesToTruncate as $table) {
                if (!Schema::hasTable($table)) {
                    continue;
                }

                DB::table($table)->truncate();
                $this->line("Truncated {$table}");
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->newLine();
        $this->info('Database cleanup complete. Master tables and admin/super-admin accounts were preserved.');

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function listTables(): array
    {
        $databaseName = Schema::getConnection()->getDatabaseName();
        $rows = DB::select('SHOW TABLES');
        $tableColumn = 'Tables_in_' . $databaseName;

        return array_values(array_filter(array_map(
            static fn ($row): string => (string) ($row->{$tableColumn} ?? ''),
            $rows
        )));
    }

    /**
     * Keep only the admin/super-admin user records and their related pivot/privilege rows.
     *
     * @param array<int, int> $preservedUserIds
     * @param array<int, string> $preservedRoles
     */
    private function cleanUsers(array $preservedUserIds, array $preservedRoles): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        if (Schema::hasTable('admin_role_user')) {
            if (!empty($preservedUserIds)) {
                DB::table('admin_role_user')->whereNotIn('user_id', $preservedUserIds)->delete();
            } else {
                DB::table('admin_role_user')->delete();
            }
        }

        if (Schema::hasTable('admin_privileges')) {
            if (!empty($preservedUserIds)) {
                DB::table('admin_privileges')->whereNotIn('user_id', $preservedUserIds)->delete();
            } else {
                DB::table('admin_privileges')->delete();
            }
        }

        if (!empty($preservedRoles)) {
            User::query()->whereNotIn('role', $preservedRoles)->delete();
        } else {
            User::query()->delete();
        }
    }
}
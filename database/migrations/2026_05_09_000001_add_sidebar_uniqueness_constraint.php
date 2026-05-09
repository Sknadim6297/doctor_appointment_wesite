<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Enforce unique sidebar assignments: one sidebar permission can only be assigned to ONE sub-admin
     * 
     * This migration adds a unique index on (page_key, group_key) for sidebar privileges where is_allowed = true.
     * Since MySQL doesn't support conditional unique indexes directly, we use a helper column approach.
     */
    public function up(): void
    {
        // Add a nullable helper column for unique constraint on sidebar permissions only
        if (!Schema::hasColumn('admin_privileges', 'sidebar_unique_marker')) {
            Schema::table('admin_privileges', function (Blueprint $table) {
                $table->string('sidebar_unique_marker', 200)->nullable()->unique()->after('is_allowed');
                $table->comment('Used for enforcing unique sidebar assignments. Null for non-sidebar or unassigned privileges.');
            });
        }

        // Create a unique index on sidebar permissions
        // Ensure no duplicate sidebar assignments exist
        DB::table('admin_privileges')
            ->where('group_key', 'sidebar')
            ->where('is_allowed', true)
            ->orderBy('user_id')
            ->orderBy('page_key')
            ->get()
            ->groupBy('page_key')
            ->filter(fn ($group) => $group->count() > 1)
            ->each(function ($duplicates) {
                // Keep the most recent one, deactivate others
                $toKeep = $duplicates->last();
                $duplicates->take($duplicates->count() - 1)->each(function ($dup) {
                    DB::table('admin_privileges')
                        ->where('id', $dup->id)
                        ->update(['is_allowed' => false, 'sidebar_unique_marker' => null]);
                });
            });

        // Now populate the marker for active sidebar assignments
        DB::table('admin_privileges')
            ->where('group_key', 'sidebar')
            ->where('is_allowed', true)
            ->cursor()
            ->each(function ($priv) {
                $marker = 'sidebar:' . $priv->page_key;
                DB::table('admin_privileges')
                    ->where('id', $priv->id)
                    ->update(['sidebar_unique_marker' => $marker]);
            });
    }

    /**
     * Revert the migration
     */
    public function down(): void
    {
        Schema::table('admin_privileges', function (Blueprint $table) {
            if (Schema::hasColumn('admin_privileges', 'sidebar_unique_marker')) {
                $table->dropUnique(['sidebar_unique_marker']);
                $table->dropColumn('sidebar_unique_marker');
            }
        });
    }
};

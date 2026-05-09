<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add action_key column if it doesn't exist
        if (!Schema::hasColumn('admin_privileges', 'action_key')) {
            Schema::table('admin_privileges', function (Blueprint $table) {
                $table->string('action_key', 50)->default('view')->after('page_key');
            });
        }

        // Add action_title column if it doesn't exist
        if (!Schema::hasColumn('admin_privileges', 'action_title')) {
            Schema::table('admin_privileges', function (Blueprint $table) {
                $table->string('action_title', 100)->default('View')->after('page_title');
            });
        }

        // Update NULL values with defaults
        DB::table('admin_privileges')
            ->whereNull('action_key')
            ->orWhere('action_key', '')
            ->update(['action_key' => 'view']);

        DB::table('admin_privileges')
            ->whereNull('action_title')
            ->orWhere('action_title', '')
            ->update(['action_title' => 'View']);
    }

    public function down(): void
    {
        Schema::table('admin_privileges', function (Blueprint $table) {
            if (Schema::hasColumn('admin_privileges', 'action_key')) {
                $table->dropColumn('action_key');
            }
            if (Schema::hasColumn('admin_privileges', 'action_title')) {
                $table->dropColumn('action_title');
            }
        });
    }
};

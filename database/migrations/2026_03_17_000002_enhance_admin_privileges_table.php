<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_privileges', function (Blueprint $table) {
            $table->string('action_key', 50)->default('view')->after('page_key');
            $table->string('action_title', 100)->default('View')->after('page_title');
        });

        DB::table('admin_privileges')->update([
            'action_key' => 'view',
            'action_title' => 'View',
        ]);

        Schema::table('admin_privileges', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'page_key']);
            $table->unique(['user_id', 'page_key', 'action_key'], 'admin_privileges_user_page_action_unique');
        });
    }

    public function down(): void
    {
        Schema::table('admin_privileges', function (Blueprint $table) {
            $table->dropUnique('admin_privileges_user_page_action_unique');
            $table->dropColumn(['action_key', 'action_title']);
            $table->unique(['user_id', 'page_key']);
        });
    }
};
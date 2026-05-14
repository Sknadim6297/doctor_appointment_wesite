<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_privileges', function (Blueprint $table): void {
            try {
                $table->dropUnique('admin_privileges_user_id_page_key_unique');
            } catch (\Throwable $e) {
                // Ignore if the legacy index is already gone.
            }

            try {
                $table->unique(['user_id', 'group_key', 'page_key', 'action_key'], 'admin_privileges_user_group_page_action_unique');
            } catch (\Throwable $e) {
                // Ignore if the composite index already exists.
            }
        });
    }

    public function down(): void
    {
        Schema::table('admin_privileges', function (Blueprint $table): void {
            try {
                $table->dropUnique('admin_privileges_user_group_page_action_unique');
            } catch (\Throwable $e) {
                // Ignore if the composite index is already gone.
            }

            try {
                $table->unique(['user_id', 'page_key'], 'admin_privileges_user_id_page_key_unique');
            } catch (\Throwable $e) {
                // Ignore if the legacy index already exists.
            }
        });
    }
};
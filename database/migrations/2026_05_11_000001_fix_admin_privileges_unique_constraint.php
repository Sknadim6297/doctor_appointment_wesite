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
                $table->dropUnique(['user_id', 'page_key']);
            } catch (\Throwable $e) {
                // The older unique index may not exist in all environments.
            }

            $table->unique(['user_id', 'page_key', 'action_key'], 'admin_privileges_user_page_action_unique');
        });
    }

    public function down(): void
    {
        Schema::table('admin_privileges', function (Blueprint $table): void {
            try {
                $table->dropUnique('admin_privileges_user_page_action_unique');
            } catch (\Throwable $e) {
                // Ignore if the new index is absent.
            }

            $table->unique(['user_id', 'page_key']);
        });
    }
};
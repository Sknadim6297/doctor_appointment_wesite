<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indexNames = collect(Schema::getConnection()->getSchemaBuilder()->getIndexes('admin_privileges'))
            ->pluck('name')
            ->map(fn ($name) => strtolower((string) $name))
            ->all();

        if (in_array('admin_privileges_user_id_page_key_unique', $indexNames, true)) {
            Schema::table('admin_privileges', function (Blueprint $table): void {
                $table->dropUnique('admin_privileges_user_id_page_key_unique');
            });
        }

        if (!in_array('admin_privileges_user_group_page_action_unique', $indexNames, true)) {
            Schema::table('admin_privileges', function (Blueprint $table): void {
                $table->unique(['user_id', 'group_key', 'page_key', 'action_key'], 'admin_privileges_user_group_page_action_unique');
            });
        }
    }

    public function down(): void
    {
        $indexNames = collect(Schema::getConnection()->getSchemaBuilder()->getIndexes('admin_privileges'))
            ->pluck('name')
            ->map(fn ($name) => strtolower((string) $name))
            ->all();

        if (in_array('admin_privileges_user_group_page_action_unique', $indexNames, true)) {
            Schema::table('admin_privileges', function (Blueprint $table): void {
                $table->dropUnique('admin_privileges_user_group_page_action_unique');
            });
        }

        if (!in_array('admin_privileges_user_id_page_key_unique', $indexNames, true)) {
            Schema::table('admin_privileges', function (Blueprint $table): void {
                $table->unique(['user_id', 'page_key'], 'admin_privileges_user_id_page_key_unique');
            });
        }
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            if (!Schema::hasColumn('enrollments', 'legacy_call_sheet_id')) {
                $table->unsignedInteger('legacy_call_sheet_id')->nullable()->unique()->after('legacy_user_id');
            }
            if (!Schema::hasColumn('enrollments', 'call_sheet_card_slug')) {
                $table->string('call_sheet_card_slug', 255)->nullable()->after('call_sheet_specialization_ids');
            }
            if (!Schema::hasColumn('enrollments', 'call_sheet_month')) {
                $table->string('call_sheet_month', 32)->nullable()->after('call_sheet_card_slug');
            }
            if (!Schema::hasColumn('enrollments', 'call_sheet_year')) {
                $table->string('call_sheet_year', 8)->nullable()->after('call_sheet_month');
            }
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            foreach (['legacy_call_sheet_id', 'call_sheet_card_slug', 'call_sheet_month', 'call_sheet_year'] as $column) {
                if (Schema::hasColumn('enrollments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

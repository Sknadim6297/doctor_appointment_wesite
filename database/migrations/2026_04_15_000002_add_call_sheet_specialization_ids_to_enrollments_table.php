<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            if (!Schema::hasColumn('enrollments', 'call_sheet_specialization_ids')) {
                $table->json('call_sheet_specialization_ids')->nullable()->after('hide_from_call_sheet');
            }
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            if (Schema::hasColumn('enrollments', 'call_sheet_specialization_ids')) {
                $table->dropColumn('call_sheet_specialization_ids');
            }
        });
    }
};

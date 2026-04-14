<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            if (!Schema::hasColumn('enrollments', 'hide_from_call_sheet')) {
                $table->boolean('hide_from_call_sheet')->default(false)->after('auto_sms_enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            if (Schema::hasColumn('enrollments', 'hide_from_call_sheet')) {
                $table->dropColumn('hide_from_call_sheet');
            }
        });
    }
};
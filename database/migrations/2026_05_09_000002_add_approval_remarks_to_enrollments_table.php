<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('enrollments', 'approval_remarks')) {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->text('approval_remarks')->nullable()->after('approved_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('enrollments', 'approval_remarks')) {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->dropColumn('approval_remarks');
            });
        }
    }
};

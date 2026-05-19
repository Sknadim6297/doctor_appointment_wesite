<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table): void {
            if (!Schema::hasColumn('enrollments', 'workflow_completed_at')) {
                $table->timestamp('workflow_completed_at')->nullable()->after('last_activity_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table): void {
            if (Schema::hasColumn('enrollments', 'workflow_completed_at')) {
                $table->dropColumn('workflow_completed_at');
            }
        });
    }
};

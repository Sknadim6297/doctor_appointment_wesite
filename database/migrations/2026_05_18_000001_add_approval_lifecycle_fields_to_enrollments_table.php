<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table): void {
            if (!Schema::hasColumn('enrollments', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('approved_at');
            }
            if (!Schema::hasColumn('enrollments', 'resubmitted_at')) {
                $table->timestamp('resubmitted_at')->nullable()->after('submitted_at');
            }
            if (!Schema::hasColumn('enrollments', 'held_at')) {
                $table->timestamp('held_at')->nullable()->after('resubmitted_at');
            }
            if (!Schema::hasColumn('enrollments', 'held_by')) {
                $table->unsignedBigInteger('held_by')->nullable()->after('held_at');
            }
            if (!Schema::hasColumn('enrollments', 'hold_reason')) {
                $table->text('hold_reason')->nullable()->after('held_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table): void {
            foreach (['hold_reason', 'held_by', 'held_at', 'resubmitted_at', 'submitted_at'] as $column) {
                if (Schema::hasColumn('enrollments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

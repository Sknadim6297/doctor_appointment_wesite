<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            if (!Schema::hasColumn('enrollments', 'current_step')) {
                $table->unsignedTinyInteger('current_step')->default(1)->after('rejection_reason');
            }

            if (!Schema::hasColumn('enrollments', 'workflow_status')) {
                $table->string('workflow_status', 30)->default('draft')->after('current_step');
            }

            if (!Schema::hasColumn('enrollments', 'is_step_incomplete')) {
                $table->boolean('is_step_incomplete')->default(true)->after('workflow_status');
            }

            if (!Schema::hasColumn('enrollments', 'last_activity_at')) {
                $table->timestamp('last_activity_at')->nullable()->after('is_step_incomplete');
            }

            if (!Schema::hasColumn('enrollments', 'completed_steps')) {
                $table->json('completed_steps')->nullable()->after('last_activity_at');
            }

            if (!Schema::hasColumn('enrollments', 'draft_data')) {
                $table->json('draft_data')->nullable()->after('completed_steps');
            }

            $table->index(['workflow_status', 'current_step'], 'idx_enrollments_workflow_step');
        });

        DB::table('enrollments')->orderBy('id')->chunkById(500, function ($enrollments): void {
            foreach ($enrollments as $enrollment) {
                $workflowStatus = 'pending_review';
                if (in_array((string) ($enrollment->status ?? ''), ['approved', 'rejected'], true)) {
                    $workflowStatus = $enrollment->status === 'approved' ? 'completed' : 'rejected';
                }

                DB::table('enrollments')
                    ->where('id', $enrollment->id)
                    ->update([
                        'current_step' => 4,
                        'workflow_status' => $workflowStatus,
                        'is_step_incomplete' => false,
                        'last_activity_at' => $enrollment->updated_at ?? $enrollment->created_at ?? now(),
                        'completed_steps' => json_encode([1, 2, 3, 4]),
                    ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            if (Schema::hasColumn('enrollments', 'draft_data')) {
                $table->dropColumn('draft_data');
            }
            if (Schema::hasColumn('enrollments', 'completed_steps')) {
                $table->dropColumn('completed_steps');
            }
            if (Schema::hasColumn('enrollments', 'last_activity_at')) {
                $table->dropColumn('last_activity_at');
            }
            if (Schema::hasColumn('enrollments', 'is_step_incomplete')) {
                $table->dropColumn('is_step_incomplete');
            }
            if (Schema::hasColumn('enrollments', 'workflow_status')) {
                $table->dropColumn('workflow_status');
            }
            if (Schema::hasColumn('enrollments', 'current_step')) {
                $table->dropColumn('current_step');
            }

            $table->dropIndex('idx_enrollments_workflow_step');
        });
    }
};
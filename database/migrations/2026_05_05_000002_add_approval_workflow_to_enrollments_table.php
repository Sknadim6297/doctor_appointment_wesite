<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            // Add approval workflow fields
            if (!Schema::hasColumn('enrollments', 'status')) {
                $table->string('status')->default('draft')->after('customer_id_no'); // draft, pending, approved, rejected
                $table->index('status');
            }

            if (!Schema::hasColumn('enrollments', 'agent_id')) {
                $table->unsignedBigInteger('agent_id')->nullable()->after('created_by'); // Agent who created the enrollment
                $table->index('agent_id');
            }

            if (!Schema::hasColumn('enrollments', 'created_by_role')) {
                $table->string('created_by_role')->nullable()->after('agent_id'); // 'super_admin' or 'agent'
            }

            if (!Schema::hasColumn('enrollments', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('created_by_role'); // Admin who approved
                $table->index('approved_by');
            }

            if (!Schema::hasColumn('enrollments', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by'); // Approval timestamp
            }

            if (!Schema::hasColumn('enrollments', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('approved_at'); // Reason for rejection if rejected
            }

            if (!Schema::hasColumn('enrollments', 'auto_sms_enabled')) {
                $table->boolean('auto_sms_enabled')->default(false)->after('rejection_reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            // Safely drop indexes if they exist
            try {
                if (Schema::hasColumn('enrollments', 'status')) {
                    $table->dropIndex(['status']);
                }
            } catch (\Exception $e) {
                // Index doesn't exist
            }

            try {
                if (Schema::hasColumn('enrollments', 'agent_id')) {
                    $table->dropIndex(['agent_id']);
                }
            } catch (\Exception $e) {
                // Index doesn't exist
            }

            try {
                if (Schema::hasColumn('enrollments', 'approved_by')) {
                    $table->dropIndex(['approved_by']);
                }
            } catch (\Exception $e) {
                // Index doesn't exist
            }

            // Drop columns if they exist
            $columnsToRemove = [];
            if (Schema::hasColumn('enrollments', 'status')) $columnsToRemove[] = 'status';
            if (Schema::hasColumn('enrollments', 'agent_id')) $columnsToRemove[] = 'agent_id';
            if (Schema::hasColumn('enrollments', 'created_by_role')) $columnsToRemove[] = 'created_by_role';
            if (Schema::hasColumn('enrollments', 'approved_by')) $columnsToRemove[] = 'approved_by';
            if (Schema::hasColumn('enrollments', 'approved_at')) $columnsToRemove[] = 'approved_at';
            if (Schema::hasColumn('enrollments', 'rejection_reason')) $columnsToRemove[] = 'rejection_reason';
            if (Schema::hasColumn('enrollments', 'auto_sms_enabled')) $columnsToRemove[] = 'auto_sms_enabled';

            if (!empty($columnsToRemove)) {
                $table->dropColumn($columnsToRemove);
            }
        });
    }
};

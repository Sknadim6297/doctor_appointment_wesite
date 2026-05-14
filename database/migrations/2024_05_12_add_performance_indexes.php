<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * These indexes are critical for performance at scale (5,000-10,000+ records).
     * Without these indexes, queries will perform full table scans.
     */
    public function up(): void
    {
        // ============================================
        // ENROLLMENTS TABLE - CRITICAL FOR PERFORMANCE
        // ============================================
        if (Schema::hasColumn('enrollments', 'created_by') && !$this->indexExists('enrollments', 'idx_enrollments_created_by')) {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->index('created_by', 'idx_enrollments_created_by');
            });
        }

        if (Schema::hasColumn('enrollments', 'agent_id') && !$this->indexExists('enrollments', 'idx_enrollments_agent_id')) {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->index('agent_id', 'idx_enrollments_agent_id');
            });
        }

        if (Schema::hasColumn('enrollments', 'status') && !$this->indexExists('enrollments', 'idx_enrollments_status')) {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->index('status', 'idx_enrollments_status');
            });
        }

        if (Schema::hasColumn('enrollments', 'plan') && !$this->indexExists('enrollments', 'idx_enrollments_plan')) {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->index('plan', 'idx_enrollments_plan');
            });
        }

        if (Schema::hasColumn('enrollments', 'specialization_id') && !$this->indexExists('enrollments', 'idx_enrollments_specialization_id')) {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->index('specialization_id', 'idx_enrollments_specialization_id');
            });
        }

        if (Schema::hasColumn('enrollments', 'created_at') && !$this->indexExists('enrollments', 'idx_enrollments_created_at')) {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->index('created_at', 'idx_enrollments_created_at');
            });
        }

        if (Schema::hasColumns('enrollments', ['status', 'created_at']) && !$this->indexExists('enrollments', 'idx_enrollments_status_created_at')) {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->index(['status', 'created_at'], 'idx_enrollments_status_created_at');
            });
        }

        // ============================================
        // LEGAL_CASES TABLE
        // ============================================
        if (Schema::hasColumn('legal_cases', 'enrollment_id') && !$this->indexExists('legal_cases', 'idx_legal_cases_enrollment_id')) {
            Schema::table('legal_cases', function (Blueprint $table) {
                $table->index('enrollment_id', 'idx_legal_cases_enrollment_id');
            });
        }

        if (Schema::hasColumn('legal_cases', 'created_by') && !$this->indexExists('legal_cases', 'idx_legal_cases_created_by')) {
            Schema::table('legal_cases', function (Blueprint $table) {
                $table->index('created_by', 'idx_legal_cases_created_by');
            });
        }

        // ============================================
        // DOCTOR_DOCUMENTS TABLE
        // ============================================
        if (Schema::hasColumns('doctor_documents', ['enrollment_id', 'document_type']) && !$this->indexExists('doctor_documents', 'idx_doctor_documents_enrollment_type')) {
            Schema::table('doctor_documents', function (Blueprint $table) {
                $table->index(['enrollment_id', 'document_type'], 'idx_doctor_documents_enrollment_type');
            });
        }

        // ============================================
        // ADMIN_ROLE_USER TABLE
        // ============================================
        if (Schema::hasColumn('admin_role_user', 'user_id') && !$this->indexExists('admin_role_user', 'idx_admin_role_user_user_id')) {
            Schema::table('admin_role_user', function (Blueprint $table) {
                $table->index('user_id', 'idx_admin_role_user_user_id');
            });
        }

        if (Schema::hasColumn('admin_role_user', 'admin_role_id') && !$this->indexExists('admin_role_user', 'idx_admin_role_user_admin_role_id')) {
            Schema::table('admin_role_user', function (Blueprint $table) {
                $table->index('admin_role_id', 'idx_admin_role_user_admin_role_id');
            });
        }

        // ============================================
        // EXPENSES TABLE
        // ============================================
        if (Schema::hasColumn('expenses', 'expense_category_id') && !$this->indexExists('expenses', 'idx_expenses_category_id')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->index('expense_category_id', 'idx_expenses_category_id');
            });
        }

        if (Schema::hasColumn('expenses', 'created_by') && !$this->indexExists('expenses', 'idx_expenses_created_by')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->index('created_by', 'idx_expenses_created_by');
            });
        }

        // ============================================
        // SALARY_RECORDS TABLE
        // ============================================
        if (Schema::hasColumn('salary_records', 'created_by') && !$this->indexExists('salary_records', 'idx_salary_records_created_by')) {
            Schema::table('salary_records', function (Blueprint $table) {
                $table->index('created_by', 'idx_salary_records_created_by');
            });
        }

        // ============================================
        // POLICY_RECEIPTS TABLE
        // ============================================
        if (Schema::hasColumn('policy_receipts', 'enrollment_id') && !$this->indexExists('policy_receipts', 'idx_policy_receipts_enrollment_id')) {
            Schema::table('policy_receipts', function (Blueprint $table) {
                $table->index('enrollment_id', 'idx_policy_receipts_enrollment_id');
            });
        }

        // ============================================
        // OTP_VERIFICATION_LOGS TABLE
        // ============================================
        if (Schema::hasColumn('otp_verification_logs', 'user_id') && !$this->indexExists('otp_verification_logs', 'idx_otp_verification_logs_user_id')) {
            Schema::table('otp_verification_logs', function (Blueprint $table) {
                $table->index('user_id', 'idx_otp_verification_logs_user_id');
            });
        }

        // ============================================
        // ADMIN_PRIVILEGES TABLE
        // ============================================
        if (Schema::hasColumns('admin_privileges', ['user_id', 'is_allowed']) && !$this->indexExists('admin_privileges', 'idx_admin_privileges_user_is_allowed')) {
            Schema::table('admin_privileges', function (Blueprint $table) {
                $table->index(['user_id', 'is_allowed'], 'idx_admin_privileges_user_is_allowed');
            });
        }

        // ============================================
        // ADMIN_ACTIVITY_LOGS TABLE
        // ============================================
        if (Schema::hasColumns('admin_activity_logs', ['module_key', 'occurred_at']) && !$this->indexExists('admin_activity_logs', 'idx_admin_activity_logs_module_occurred_at')) {
            Schema::table('admin_activity_logs', function (Blueprint $table) {
                $table->index(['module_key', 'occurred_at'], 'idx_admin_activity_logs_module_occurred_at');
            });
        }

        // ============================================
        // RENEWAL_CHEQUE_DEPOSITS TABLE
        // ============================================
        if (Schema::hasColumn('renewal_cheque_deposits', 'enrollment_id') && !$this->indexExists('renewal_cheque_deposits', 'idx_renewal_cheque_deposits_enrollment_id')) {
            Schema::table('renewal_cheque_deposits', function (Blueprint $table) {
                $table->index('enrollment_id', 'idx_renewal_cheque_deposits_enrollment_id');
            });
        }

        if (Schema::hasColumn('renewal_cheque_deposits', 'status') && !$this->indexExists('renewal_cheque_deposits', 'idx_renewal_cheque_deposits_status')) {
            Schema::table('renewal_cheque_deposits', function (Blueprint $table) {
                $table->index('status', 'idx_renewal_cheque_deposits_status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_enrollments_created_by');
            $table->dropIndexIfExists('idx_enrollments_agent_id');
            $table->dropIndexIfExists('idx_enrollments_status');
            $table->dropIndexIfExists('idx_enrollments_plan');
            $table->dropIndexIfExists('idx_enrollments_specialization_id');
            $table->dropIndexIfExists('idx_enrollments_created_at');
            $table->dropIndexIfExists('idx_enrollments_status_created_at');
        });

        Schema::table('legal_cases', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_legal_cases_enrollment_id');
            $table->dropIndexIfExists('idx_legal_cases_created_by');
        });

        Schema::table('doctor_documents', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_doctor_documents_enrollment_type');
        });

        Schema::table('admin_role_user', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_admin_role_user_user_id');
            $table->dropIndexIfExists('idx_admin_role_user_role_id');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_expenses_category_id');
            $table->dropIndexIfExists('idx_expenses_created_by');
        });

        Schema::table('salary_records', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_salary_records_created_by');
        });

        Schema::table('policy_receipts', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_policy_receipts_enrollment_id');
        });

        Schema::table('otp_verification_logs', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_otp_verification_logs_user_id');
        });

        Schema::table('admin_privileges', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_admin_privileges_user_is_allowed');
        });

        Schema::table('admin_activity_logs', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_admin_activity_logs_module_occurred_at');
        });

        Schema::table('renewal_cheque_deposits', function (Blueprint $table) {
            $table->dropIndexIfExists('idx_renewal_cheque_deposits_enrollment_id');
            $table->dropIndexIfExists('idx_renewal_cheque_deposits_status');
        });
    }

    /**
     * Check if an index exists on a table.
     * 
     * @param string $table
     * @param string $indexName
     * @return bool
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = DB::select("SHOW INDEXES FROM {$table}");
        foreach ($indexes as $index) {
            if ($index->Key_name === $indexName) {
                return true;
            }
        }
        return false;
    }
};

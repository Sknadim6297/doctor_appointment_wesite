<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            // Add renewal and policy tracking fields
            if (!Schema::hasColumn('enrollments', 'renewal_date')) {
                $table->date('renewal_date')->nullable()->after('payment_cash_date');
            }

            if (!Schema::hasColumn('enrollments', 'policy_date')) {
                $table->date('policy_date')->nullable()->after('renewal_date');
            }

            if (!Schema::hasColumn('enrollments', 'last_renewal_date')) {
                $table->date('last_renewal_date')->nullable()->after('policy_date');
            }

            if (!Schema::hasColumn('enrollments', 'coverage')) {
                $table->decimal('coverage', 10, 2)->nullable()->after('last_renewal_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            if (Schema::hasColumn('enrollments', 'renewal_date')) {
                $table->dropColumn('renewal_date');
            }
            if (Schema::hasColumn('enrollments', 'policy_date')) {
                $table->dropColumn('policy_date');
            }
            if (Schema::hasColumn('enrollments', 'last_renewal_date')) {
                $table->dropColumn('last_renewal_date');
            }
            if (Schema::hasColumn('enrollments', 'coverage')) {
                $table->dropColumn('coverage');
            }
        });
    }
};

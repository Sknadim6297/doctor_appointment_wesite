<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            if (! Schema::hasColumn('enrollments', 'policy_no')) {
                $table->string('policy_no', 255)->nullable()->after('money_rc_no');
            }
            if (! Schema::hasColumn('enrollments', 'doctor_money_reciept_year')) {
                $table->string('doctor_money_reciept_year', 10)->nullable()->after('doctor_money_reciept_no');
            }
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            if (Schema::hasColumn('enrollments', 'policy_no')) {
                $table->dropColumn('policy_no');
            }
            if (Schema::hasColumn('enrollments', 'doctor_money_reciept_year')) {
                $table->dropColumn('doctor_money_reciept_year');
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->unsignedInteger('doctor_money_reciept_no')->nullable()->after('money_rc_no');
            $table->index('doctor_money_reciept_no');
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropIndex(['doctor_money_reciept_no']);
            $table->dropColumn('doctor_money_reciept_no');
        });
    }
};
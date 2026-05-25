<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_tbl_employee_salary', function (Blueprint $table) {
            $table->unsignedInteger('salary_id')->primary();
            $table->unsignedInteger('employee_id')->index();
            $table->string('month')->default('');
            $table->string('year')->default('');
            $table->string('monthly_salary')->default('0');
            $table->unsignedInteger('total_login_day')->default(0);
            $table->string('earned_salary')->default('0');
            $table->string('deducted_salary')->default('0');
            $table->string('additional_deduction')->default('0');
            $table->text('additional_deduction_reason')->nullable();
            $table->string('intensive')->default('0');
            $table->text('intensive_for')->nullable();
            $table->string('advance')->default('0');
            $table->string('advance_deduction')->default('0');
            $table->string('office_duty')->default('0');
            $table->string('bonus')->default('0');
            $table->string('pf')->default('0');
            $table->string('ptax')->default('0');
            $table->string('esi')->default('0');
            $table->string('total_salary')->default('0');
            $table->unsignedInteger('absense')->default(0);
            $table->text('absense_reason')->nullable();
            $table->string('checque_no')->nullable();
            $table->string('bank_name')->nullable();
            $table->date('created_date')->nullable();
            $table->unsignedInteger('created_by')->default(0);
            $table->date('edited_date')->nullable();
            $table->unsignedInteger('edited_by')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_tbl_employee_salary');
    }
};

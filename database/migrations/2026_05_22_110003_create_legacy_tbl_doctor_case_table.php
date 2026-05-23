<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_tbl_doctor_case', function (Blueprint $table) {
            $table->unsignedInteger('case_id')->primary();
            $table->unsignedInteger('doctor_id')->default(0)->index();
            $table->unsignedInteger('cat_id')->default(0);
            $table->string('doctor_mobile')->nullable();
            $table->text('doctor_email')->nullable();
            $table->string('case_number')->nullable();
            $table->string('court')->nullable();
            $table->text('case_details')->nullable();
            $table->date('next_date')->nullable();
            $table->date('filling_date')->nullable();
            $table->date('closing_date')->nullable();
            $table->string('complainant_name')->nullable();
            $table->string('advocat_mobile')->nullable();
            $table->string('advocat_mail')->nullable();
            $table->string('mail_link')->nullable();
            $table->unsignedInteger('created_by')->default(0);
            $table->unsignedInteger('edited_by')->default(0);
            $table->date('created_on')->nullable();
            $table->date('edited_on')->nullable();
            $table->string('created_month')->nullable();
            $table->string('created_year')->nullable();
            $table->string('cheque_no')->nullable();
            $table->string('bank')->nullable();
            $table->string('payment_amount')->nullable();
            $table->string('money_receipt')->nullable();
            $table->string('bank_branch')->nullable();
            $table->date('check_date')->nullable();
            $table->date('appear_date')->nullable();
            $table->text('stage')->nullable();
            $table->text('case_link')->nullable();
            $table->string('court_year')->nullable();
            $table->text('court_address')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_tbl_doctor_case');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->nullable()->constrained('enrollments')->nullOnDelete();
            $table->string('doctor_name');
            $table->string('doctor_phone')->nullable();
            $table->string('doctor_mail')->nullable();
            $table->string('case_number')->nullable();
            $table->unsignedSmallInteger('court_year')->nullable();
            $table->string('court')->nullable();
            $table->text('court_address')->nullable();
            $table->string('case_cat')->nullable();
            $table->text('stage')->nullable();
            $table->longText('case_details')->nullable();
            $table->string('advocat_mobile')->nullable();
            $table->string('advocat_mail')->nullable();
            $table->date('appear_date')->nullable();
            $table->date('next_date')->nullable();
            $table->date('filling_date')->nullable();
            $table->string('complainant_name')->nullable();
            $table->string('mail_link')->nullable();
            $table->boolean('direct_payment')->default(false);
            $table->string('money_reciept_no')->nullable();
            $table->string('payment_cheque_no')->nullable();
            $table->string('direct_payment_bank')->nullable();
            $table->string('bank_branch')->nullable();
            $table->decimal('direct_payment_amount', 12, 2)->nullable();
            $table->date('check_date')->nullable();
            $table->string('case_link')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_cases');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->string('customer_id_no')->nullable();
            $table->string('money_rc_no')->nullable();
            $table->string('agent_name')->nullable();
            $table->string('agent_phone_no')->nullable();

            // Proposer details
            $table->string('doctor_name');
            $table->string('doctor_address')->nullable();
            $table->string('country')->nullable();
            $table->string('country_name')->nullable();
            $table->string('state')->nullable();
            $table->string('state_name')->nullable();
            $table->string('city')->nullable();
            $table->string('city_name')->nullable();
            $table->string('postcode')->nullable();
            $table->string('mobile1')->nullable();
            $table->string('mobile2')->nullable();
            $table->string('doctor_email')->nullable();
            $table->date('dob')->nullable();
            $table->string('qualification')->nullable();
            $table->json('qualification_year')->nullable();
            $table->string('medical_registration_no')->nullable();
            $table->string('year_of_reg')->nullable();
            $table->text('clinic_address')->nullable();
            $table->string('aadhar_card_no')->nullable();
            $table->string('pan_card_no')->nullable();

            // Payment details
            $table->unsignedBigInteger('specialization_id')->nullable();
            $table->string('payment_mode')->nullable();
            $table->tinyInteger('plan')->nullable();  // 1=Normal, 2=HighRisk, 3=Combo
            $table->string('plan_name')->nullable();
            $table->unsignedBigInteger('coverage_id')->nullable();
            $table->decimal('service_amount', 12, 2)->nullable();
            $table->decimal('payment_amount', 12, 2)->nullable();
            $table->decimal('total_amount', 12, 2)->nullable();
            $table->tinyInteger('payment_method')->nullable(); // 1=Cheque, 2=Cash, 3=UPI
            $table->string('payment_cheque')->nullable();
            $table->string('payment_bank_name')->nullable();
            $table->string('payment_branch_name')->nullable();
            $table->string('payment_upi_transaction_id')->nullable();
            $table->date('payment_cash_date')->nullable();
            $table->boolean('bond_to_mail')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};

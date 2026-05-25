<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_tbl_renewal_payment', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('doctor_id')->index();
            $table->string('payment_type', 20)->nullable();
            $table->string('cheque_no', 255)->nullable();
            $table->string('bank_name', 255)->nullable();
            $table->string('bank_branch', 255)->nullable();
            $table->string('transaction_no', 255)->nullable();
            $table->string('payment_date', 50)->nullable();
            $table->string('payment_amount', 255)->nullable();
            $table->string('payment_mode', 255)->nullable();
            $table->string('money_reciept_no', 255)->nullable();
            $table->string('checque_file', 255)->nullable();
            $table->string('policy_no', 255)->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedTinyInteger('plan_id')->default(0);
            $table->enum('from_old', ['yes', 'no'])->default('no');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_tbl_renewal_payment');
    }
};
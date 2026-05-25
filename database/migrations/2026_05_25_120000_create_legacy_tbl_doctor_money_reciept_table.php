<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_tbl_doctor_money_reciept', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('doctor_id')->index();
            $table->unsignedInteger('money_reciept_no')->nullable();
            $table->date('money_reciept_date')->nullable();
            $table->unsignedSmallInteger('money_reciept_year')->nullable();
            $table->string('payment_amnt', 500)->nullable();
            $table->string('enrollment_bond', 255)->nullable();
            $table->string('renewal_bond', 255)->nullable();
            $table->string('payment_for', 32)->default('renewal');
            $table->unsignedBigInteger('payment_id')->default(0);
            $table->string('from_old', 8)->default('no');
            $table->text('money_remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_tbl_doctor_money_reciept');
    }
};
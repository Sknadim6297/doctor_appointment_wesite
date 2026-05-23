<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_tbl_doctor_insurance', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->string('membership_no')->nullable();
            $table->unsignedInteger('doctor_id')->index();
            $table->unsignedTinyInteger('plan_id')->nullable();
            $table->string('payment_mode')->nullable();
            $table->string('legal_service')->nullable();
            $table->string('insurance_coverage')->nullable();
            $table->string('insurance_amount')->nullable();
            $table->string('medeforum_amount')->nullable();
            $table->date('enrollment_date')->nullable();
            $table->date('renewal_date')->nullable();
            $table->string('policy_no')->nullable();
            $table->date('policy_date')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_tbl_doctor_insurance');
    }
};

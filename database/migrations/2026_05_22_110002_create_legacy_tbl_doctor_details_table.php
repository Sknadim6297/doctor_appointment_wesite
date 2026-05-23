<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_tbl_doctor_details', function (Blueprint $table) {
            $table->unsignedInteger('doctor_detais_id')->primary();
            $table->unsignedInteger('user_id')->nullable()->index();
            $table->unsignedInteger('specilality_id')->nullable();
            $table->string('qualification')->nullable();
            $table->string('qualification_year')->nullable();
            $table->string('medical_reg_no')->nullable();
            $table->string('medical_reg_year')->nullable();
            $table->text('clinic_address')->nullable();
            $table->string('agent_name')->nullable();
            $table->string('agent_phone')->nullable();
            $table->string('bulk_upload')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_tbl_doctor_details');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_tbl_doctor_policy', function (Blueprint $table) {
            $table->unsignedInteger('policy_id')->primary();
            $table->unsignedInteger('doctor_id')->default(0)->index();
            $table->string('year')->nullable();
            $table->text('policy_no')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_tbl_doctor_policy');
    }
};

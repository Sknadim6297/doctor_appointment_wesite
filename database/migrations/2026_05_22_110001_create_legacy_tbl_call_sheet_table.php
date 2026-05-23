<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_tbl_call_sheet', function (Blueprint $table) {
            $table->unsignedInteger('call_sheet_id')->primary();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();
            $table->text('specialization')->nullable();
            $table->text('card')->nullable();
            $table->date('created_date')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->date('edited_date')->nullable();
            $table->unsignedInteger('edited_by')->nullable();
            $table->string('month', 32)->nullable();
            $table->string('year', 8)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_tbl_call_sheet');
    }
};

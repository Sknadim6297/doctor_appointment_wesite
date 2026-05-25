<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_tbl_expensive_category', function (Blueprint $table) {
            $table->unsignedInteger('expensive_cat_id')->primary();
            $table->string('expensive_cat_name');
        });

        Schema::create('legacy_tbl_expensive', function (Blueprint $table) {
            $table->unsignedInteger('expense_id')->primary();
            $table->unsignedInteger('expense_cat_id')->index();
            $table->string('customer_name')->default('');
            $table->date('expense_date')->nullable();
            $table->string('expense_amount')->default('0');
            $table->string('payment_mode', 32)->default('cash');
            $table->string('cheque_no')->default('');
            $table->string('bank_name')->default('');
            $table->text('note')->nullable();
            $table->text('voucher')->nullable();
            $table->string('expensive_month')->default('');
            $table->string('expensive_year')->default('');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_tbl_expensive');
        Schema::dropIfExists('legacy_tbl_expensive_category');
    }
};
